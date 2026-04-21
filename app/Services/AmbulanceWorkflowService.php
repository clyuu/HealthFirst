<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ambulance;
use App\Models\AmbulanceLocation;
use App\Models\Dispatch;
use App\Models\Incident;
use App\Models\InjurySession;
use App\Models\MedicalDocument;
use App\Models\PatientVital;
use RuntimeException;

final class AmbulanceWorkflowService
{
    public function updateLocation(int $paramedicUserId, int $incidentId, float $latitude, float $longitude, ?float $speed = null): array
    {
        $ambulance = (new Ambulance())->findByParamedicUserId($paramedicUserId);
        if ($ambulance === null) {
            throw new RuntimeException('No active ambulance is assigned to this paramedic.');
        }

        (new Ambulance())->updateLocation((int) $ambulance['ambulance_id'], $latitude, $longitude);
        (new AmbulanceLocation())->store([
            'ambulance_id' => $ambulance['ambulance_id'],
            'incident_id' => $incidentId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_kmh' => $speed,
        ]);

        return [
            'ambulance_id' => (int) $ambulance['ambulance_id'],
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    public function pickupPatient(int $paramedicUserId, int $incidentId, float $latitude, float $longitude): array
    {
        $ambulance = (new Ambulance())->findByParamedicUserId($paramedicUserId);
        $incident = (new Incident())->findDetailedById($incidentId);

        if ($ambulance === null || $incident === null) {
            throw new RuntimeException('Unable to resolve ambulance pickup context.');
        }

        $route = (new MapsService())->computeRoute(
            $latitude,
            $longitude,
            (float) $incident['hospital_latitude'],
            (float) $incident['hospital_longitude']
        );

        (new Dispatch())->markPickup($incidentId, $route['eta_seconds']);
        (new Ambulance())->updateStatus((int) $ambulance['ambulance_id'], 'en_route_hospital');
        (new Incident())->updateStatus($incidentId, 'patient_picked_up', $paramedicUserId, 'Patient picked up and transport to hospital started.');

        return [
            'eta_seconds' => $route['eta_seconds'],
            'distance_meters' => $route['distance_meters'],
            'encoded_polyline' => $route['encoded_polyline'],
            'hospital_latitude' => (float) $incident['hospital_latitude'],
            'hospital_longitude' => (float) $incident['hospital_longitude'],
        ];
    }

    public function saveVitals(array $data): void
    {
        (new PatientVital())->create($data);
    }

    public function startInjurySession(int $incidentId, int $userId, ?string $specialNote = null): array
    {
        $aiSession = (new AIClient())->startInjurySession($incidentId, $userId);
        $sessionId = (new InjurySession())->create(
            $incidentId,
            $userId,
            $aiSession['session_token'] ?? bin2hex(random_bytes(12)),
            $specialNote
        );

        return [
            'session_id' => $sessionId,
            'session_token' => $aiSession['session_token'] ?? null,
        ];
    }

    public function analyzeImage(int $sessionId, array $file): array
    {
        $session = (new InjurySession())->findById($sessionId);
        if ($session === null) {
            throw new RuntimeException('Injury session not found.');
        }

        $stored = (new DocumentService())->storeUploadedFile($file, 'injuries');
        $analysis = (new AIClient())->analyzeInjury($sessionId, $stored['absolute_path']);

        (new InjurySession())->addPrediction($sessionId, (int) $session['incident_id'], [
            'image_path' => $stored['file_path'],
            'predicted_label' => $analysis['predicted_label'],
            'confidence_score' => $analysis['confidence'],
            'burns_probability' => $analysis['probabilities']['Burns'] ?? 0,
            'cuts_probability' => $analysis['probabilities']['Cuts & Bleeding'] ?? 0,
            'normal_probability' => $analysis['probabilities']['Normal (No Visible Injury)'] ?? 0,
        ]);

        return [
            'stored' => $stored['file_path'],
            'analysis' => $analysis,
        ];
    }

    public function finalizeSession(int $sessionId, array $context): array
    {
        $sessionModel = new InjurySession();
        $session = $sessionModel->findById($sessionId);
        if ($session === null) {
            throw new RuntimeException('Injury session not found.');
        }

        $incident = (new Incident())->findDetailedById((int) $session['incident_id']);
        if ($incident === null) {
            throw new RuntimeException('Incident not found for injury session.');
        }

        $report = (new AIClient())->finalizeInjurySession($sessionId, [
            'patient_name' => $incident['patient_name'],
            'incident_id' => $incident['incident_id'],
            'special_note' => $context['special_note'] ?? $session['special_note'],
        ]);

        $sessionModel->finalize($sessionId, [
            'overall_severity' => $report['overall_severity'] ?? 'Moderate',
            'summary_json' => json_encode($report['summary'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'report_file_path' => $report['report_file_path'] ?? null,
            'special_note' => $context['special_note'] ?? $session['special_note'],
        ]);

        if (!empty($report['report_file_path'])) {
            (new MedicalDocument())->create([
                'user_id' => $incident['user_id'],
                'incident_id' => $incident['incident_id'],
                'hospital_id' => $incident['selected_hospital_id'],
                'uploaded_by_user_id' => $session['started_by_user_id'],
                'document_category' => 'injury_report',
                'source_type' => 'ai_generated',
                'title' => 'AI Preliminary Injury Report',
                'description' => 'Auto-generated from paramedic uploaded injury photos.',
                'file_path' => $report['report_file_path'],
                'mime_type' => 'application/pdf',
                'file_size' => @filesize(storage_path($report['report_file_path'])) ?: 0,
            ]);
        }

        (new Incident())->updateStatus((int) $incident['incident_id'], 'en_route_hospital', (int) $session['started_by_user_id'], 'Injury report finalized and shared with hospital.');

        return $report;
    }
}

