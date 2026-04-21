<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Ambulance;
use App\Models\Incident;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AmbulanceWorkflowService;
use Throwable;

final class AmbulanceController extends Controller
{
    public function dashboard(): void
    {
        $paramedic = Auth::user();

        $this->render('ambulance/dashboard', [
            'title' => 'Ambulance Dashboard',
            'ambulance' => (new Ambulance())->findByParamedicUserId((int) $paramedic['user_id']),
            'incidents' => (new Incident())->listAmbulanceQueue((int) $paramedic['user_id']),
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function incidents(): void
    {
        $this->json([
            'incidents' => (new Incident())->listAmbulanceQueue((int) Auth::id()),
        ]);
    }

    public function lookupPatient(int $incidentId): void
    {
        $this->validateCsrf();
        $incident = (new Incident())->findDetailedById($incidentId);
        if ($incident === null) {
            $this->json(['error' => 'Incident not found.'], 404);
        }

        $valid = false;
        if (!empty($_POST['public_token'])) {
            $qr = (new QrCode())->findByToken(trim((string) $_POST['public_token']));
            $valid = $qr !== null && (int) $qr['user_id'] === (int) $incident['user_id'];
        }

        if (!empty($_POST['nic_number'])) {
            $patient = (new User())->findByNic(trim((string) $_POST['nic_number']));
            $valid = $patient !== null && (int) $patient['user_id'] === (int) $incident['user_id'];
        }

        if (!$valid) {
            $this->json(['error' => 'Patient QR/NIC did not match this incident.'], 422);
        }

        $this->json(['patient' => $incident]);
    }

    public function updateLocation(int $incidentId): void
    {
        $this->validateCsrf();
        try {
            $result = (new AmbulanceWorkflowService())->updateLocation(
                (int) Auth::id(),
                $incidentId,
                (float) $_POST['latitude'],
                (float) $_POST['longitude'],
                isset($_POST['speed_kmh']) ? (float) $_POST['speed_kmh'] : null
            );
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function pickupPatient(int $incidentId): void
    {
        $this->validateCsrf();
        try {
            $result = (new AmbulanceWorkflowService())->pickupPatient(
                (int) Auth::id(),
                $incidentId,
                (float) $_POST['latitude'],
                (float) $_POST['longitude']
            );
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function saveVitals(int $incidentId): void
    {
        $this->validateCsrf();
        try {
            (new AmbulanceWorkflowService())->saveVitals([
                'incident_id' => $incidentId,
                'recorded_by_user_id' => (int) Auth::id(),
                'heart_rate' => $_POST['heart_rate'] ?? null,
                'systolic_bp' => $_POST['systolic_bp'] ?? null,
                'diastolic_bp' => $_POST['diastolic_bp'] ?? null,
                'spo2' => $_POST['spo2'] ?? null,
                'temperature_c' => $_POST['temperature_c'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ]);
            $this->json(['message' => 'Vitals saved.']);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function startInjurySession(int $incidentId): void
    {
        $this->validateCsrf();
        try {
            $session = (new AmbulanceWorkflowService())->startInjurySession(
                $incidentId,
                (int) Auth::id(),
                trim((string) ($_POST['special_note'] ?? ''))
            );
            $this->json($session);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function analyzeImage(int $sessionId): void
    {
        $this->validateCsrf();
        try {
            $result = (new AmbulanceWorkflowService())->analyzeImage($sessionId, $_FILES['injury_photo']);
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function finalizeSession(int $sessionId): void
    {
        $this->validateCsrf();
        try {
            $result = (new AmbulanceWorkflowService())->finalizeSession($sessionId, [
                'special_note' => trim((string) ($_POST['special_note'] ?? '')),
            ]);
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }
}
