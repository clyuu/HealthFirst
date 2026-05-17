<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\DoctorCaseAssignment;
use App\Models\Incident;
use App\Models\MedicalDocument;

final class DocumentController extends Controller
{
    public function download(string|int $documentId): void
    {
        $documentId = (int) $documentId;
        $document = (new MedicalDocument())->findById($documentId);
        if ($document === null || !$this->canAccessDocument($document)) {
            http_response_code(404);
            exit('Document not found.');
        }

        $absolutePath = storage_path($document['file_path']);
        if (!is_file($absolutePath)) {
            http_response_code(404);
            exit('Stored document file not found.');
        }
        $inline = !empty($_GET['inline']);

        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($document['file_path']) . '"');
        readfile($absolutePath);
        exit;
    }

    public function injuryReport(string|int $incidentId): void
    {
        $incidentId = (int) $incidentId;
        $incident = (new Incident())->findDetailedById($incidentId);
        if ($incident === null || empty($incident['report_file_path']) || !$this->canAccessIncident($incident)) {
            http_response_code(404);
            exit('Report not found.');
        }

        $absolutePath = storage_path($incident['report_file_path']);
        if (!is_file($absolutePath)) {
            http_response_code(404);
            exit('Stored report file not found.');
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="injury-report-' . $incidentId . '.pdf"');
        readfile($absolutePath);
        exit;
    }

    private function canAccessDocument(array $document): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        if ($user['role_slug'] === 'system_admin') {
            return true;
        }

        if ($user['role_slug'] === 'patient') {
            return (int) $document['user_id'] === (int) $user['user_id'];
        }

        if ($user['role_slug'] === 'doctor') {
            return (new DoctorCaseAssignment())->doctorHasAccessToUser((int) $user['user_id'], (int) $document['user_id']);
        }

        if (in_array($user['role_slug'], ['doctor', 'hospital_staff', 'hospital_admin', 'paramedic'], true) && !empty($document['incident_id'])) {
            $incident = (new Incident())->findDetailedById((int) $document['incident_id']);
            return $incident !== null && $this->canAccessIncident($incident);
        }

        return false;
    }

    private function canAccessIncident(array $incident): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return match ($user['role_slug']) {
            'system_admin' => true,
            'patient' => (int) $incident['user_id'] === (int) $user['user_id'],
            'doctor' => (int) ($incident['doctor_user_id'] ?? 0) === (int) $user['user_id']
                || (int) ($incident['hospital_id'] ?? $incident['selected_hospital_id'] ?? 0) === (int) ($user['hospital_id'] ?? 0),
            'hospital_staff', 'hospital_admin', 'paramedic' => (int) ($incident['hospital_id'] ?? $incident['selected_hospital_id'] ?? 0) === (int) ($user['hospital_id'] ?? 0),
            default => false,
        };
    }
}
