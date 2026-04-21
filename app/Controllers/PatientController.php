<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\EmergencyContact;
use App\Models\Hospital;
use App\Models\MedicalDocument;
use App\Models\MedicalProfile;
use App\Models\QrCode;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\MapsService;
use App\Services\QrCodeService;
use Throwable;

final class PatientController extends Controller
{
    public function dashboard(): void
    {
        $user = Auth::user();
        $userId = (int) $user['user_id'];

        $qr = (new QrCodeService())->ensureForUser($userId);
        $profile = (new MedicalProfile())->findByUserId($userId);
        $contacts = (new EmergencyContact())->listByUserId($userId);
        $documents = (new MedicalDocument())->listByUserId($userId);

        $this->render('patient/dashboard', [
            'title' => 'Patient Dashboard',
            'user' => $user,
            'profile' => $profile,
            'contacts' => $contacts,
            'documents' => $documents,
            'qr' => $qr,
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function saveProfile(): void
    {
        $this->validateCsrf();
        $user = Auth::user();

        (new User())->updateSelf((int) $user['user_id'], [
            'full_name' => trim((string) $_POST['full_name']),
            'phone' => trim((string) $_POST['phone']),
            'address' => trim((string) $_POST['address']),
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'profile_latitude' => $_POST['profile_latitude'] ?? null,
            'profile_longitude' => $_POST['profile_longitude'] ?? null,
        ]);

        (new MedicalProfile())->upsert((int) $user['user_id'], [
            'blood_group' => $_POST['blood_group'] ?? 'Unknown',
            'allergies' => $_POST['allergies'] ?? null,
            'chronic_conditions' => $_POST['chronic_conditions'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'emergency_phone' => $_POST['emergency_phone'] ?? null,
        ]);

        Flash::success('Profile updated successfully.');
        $this->redirect('/patient/dashboard');
    }

    public function saveContact(): void
    {
        $this->validateCsrf();

        (new EmergencyContact())->create((int) Auth::id(), [
            'contact_name' => trim((string) $_POST['contact_name']),
            'relationship' => trim((string) ($_POST['relationship'] ?? '')),
            'phone_number' => trim((string) $_POST['phone_number']),
            'is_primary' => !empty($_POST['is_primary']),
        ]);

        Flash::success('Emergency contact saved.');
        $this->redirect('/patient/dashboard');
    }

    public function uploadDocument(): void
    {
        $this->validateCsrf();

        try {
            $stored = (new DocumentService())->storeUploadedFile($_FILES['document'], 'documents');
            (new MedicalDocument())->create([
                'user_id' => (int) Auth::id(),
                'uploaded_by_user_id' => (int) Auth::id(),
                'document_category' => $_POST['document_category'] ?? 'medical_history',
                'source_type' => 'patient_upload',
                'title' => trim((string) $_POST['title']),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'file_path' => $stored['file_path'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
            ]);
            Flash::success('Medical document uploaded.');
        } catch (Throwable $exception) {
            Flash::error('Unable to upload document: ' . $exception->getMessage());
        }

        $this->redirect('/patient/dashboard');
    }

    public function downloadQr(): void
    {
        $qr = (new QrCode())->findByUserId((int) Auth::id());
        if ($qr === null) {
            http_response_code(404);
            exit('QR code not found.');
        }

        $path = storage_path($qr['image_path']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('QR image file not found.');
        }
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="healthfirst-qr.png"');
        readfile($path);
        exit;
    }

    public function printQr(): void
    {
        $qr = (new QrCode())->findByUserId((int) Auth::id());
        $user = Auth::user();

        $this->render('patient/print_qr', [
            'title' => 'Print QR Sticker',
            'qr' => $qr,
            'user' => $user,
        ], '');
    }

    public function nearbyHospitals(): void
    {
        $user = Auth::user();
        $latitude = (float) ($_GET['latitude'] ?? $user['profile_latitude'] ?? 0);
        $longitude = (float) ($_GET['longitude'] ?? $user['profile_longitude'] ?? 0);

        if ($latitude === 0.0 || $longitude === 0.0) {
            $this->json(['error' => 'Location coordinates are required.'], 422);
        }

        $hospitals = (new MapsService())->rankHospitals($latitude, $longitude, 5);
        $this->json(['hospitals' => $hospitals]);
    }
}
