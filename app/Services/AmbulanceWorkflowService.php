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
    private const DUPLICATE_LOCATION_RADIUS_METERS = 100;

    public function updateLocation(int $paramedicUserId, int $incidentId, float $latitude, float $longitude, ?float $speed = null): array
    {
        $ambulance = (new Ambulance())->findByParamedicUserId($paramedicUserId);
        if ($ambulance === null) {
            throw new RuntimeException('No active ambulance is assigned to this paramedic.');
        }

        $ambulanceId = (int) $ambulance['ambulance_id'];
        $locations = new AmbulanceLocation();
        $latest = $locations->latestForAmbulanceIncident($ambulanceId, $incidentId);
        $shouldStoreLocation = $latest === null || $this->distanceMeters(
            (float) $latest['latitude'],
            (float) $latest['longitude'],
            $latitude,
            $longitude
        ) >= self::DUPLICATE_LOCATION_RADIUS_METERS;

        (new Ambulance())->updateLocation((int) $ambulance['ambulance_id'], $latitude, $longitude);
        if ($shouldStoreLocation) {
            $locations->store([
                'ambulance_id' => $ambulanceId,
                'incident_id' => $incidentId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'speed_kmh' => $speed,
            ]);
        }

        return [
            'ambulance_id' => $ambulanceId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_recorded' => $shouldStoreLocation,
        ];
    }

    public function pickupPatient(int $paramedicUserId, int $incidentId, float $latitude, float $longitude): array
    {
        $ambulance = (new Ambulance())->findByParamedicUserId($paramedicUserId);
        $incident = (new Incident())->findDetailedById($incidentId);

        if ($ambulance === null || $incident === null) {
            throw new RuntimeException('Unable to resolve ambulance pickup context.');
        }

        (new Ambulance())->updateLocation((int) $ambulance['ambulance_id'], $latitude, $longitude);
        (new AmbulanceLocation())->store([
            'ambulance_id' => $ambulance['ambulance_id'],
            'incident_id' => $incidentId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'source' => 'gps',
        ]);

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

    public function arriveAtHospital(int $paramedicUserId, int $incidentId): array
    {
        $ambulance = (new Ambulance())->findByParamedicUserId($paramedicUserId);
        $incident = (new Incident())->findDetailedById($incidentId);

        if ($ambulance === null || $incident === null) {
            throw new RuntimeException('Unable to resolve ambulance arrival context.');
        }

        if ((int) ($incident['ambulance_id'] ?? 0) !== (int) $ambulance['ambulance_id']) {
            throw new RuntimeException('This incident is not assigned to your ambulance.');
        }

        if (!in_array((string) ($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true)) {
            throw new RuntimeException('Patient must be picked up before hospital arrival can be confirmed.');
        }

        (new Dispatch())->markArrivedAtHospital($incidentId);
        (new Ambulance())->updateStatus((int) $ambulance['ambulance_id'], 'available');
        (new Incident())->updateStatus($incidentId, 'en_route_hospital', $paramedicUserId, 'Ambulance confirmed patient arrival at hospital.');

        return [
            'incident_id' => $incidentId,
            'status' => 'arrived_hospital',
            'message' => 'Patient arrival at hospital confirmed.',
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusMeters * (2 * atan2(sqrt($a), sqrt(1 - $a)));
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

        $storedPredictions = array_map(static fn (array $prediction): array => [
            'predicted_label' => $prediction['predicted_label'] ?? 'Normal (No Visible Injury)',
            'confidence_score' => (float) ($prediction['confidence_score'] ?? 0),
            'burns_probability' => (float) ($prediction['burns_probability'] ?? 0),
            'cuts_probability' => (float) ($prediction['cuts_probability'] ?? 0),
            'normal_probability' => (float) ($prediction['normal_probability'] ?? 0),
        ], $sessionModel->predictions($sessionId));

        $report = (new AIClient())->finalizeInjurySession($sessionId, [
            'patient_name' => $incident['patient_name'],
            'incident_id' => $incident['incident_id'],
            'special_note' => $context['special_note'] ?? $session['special_note'],
            'predictions' => $storedPredictions,
        ]);

        $sessionModel->finalize($sessionId, [
            'overall_severity' => $report['overall_severity'] ?? 'Moderate',
            'summary_json' => json_encode($report['summary'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'report_file_path' => $report['report_file_path'] ?? null,
            'special_note' => $context['special_note'] ?? $session['special_note'],
        ]);

        if (!empty($report['report_file_path'])) {
            $this->refreshReportTemplate((int) $incident['incident_id']);
        }

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

    private function refreshReportTemplate(int $incidentId): void
    {
        $python = (string) config_value('services.python_bin', 'python');
        $script = base_path('bin/regenerate_injury_reports.py');
        if (!is_file($script)) {
            return;
        }

        $command = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' --incident ' . $incidentId;
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            error_log('Injury report template refresh failed: ' . implode(' | ', $output));
        }
    }
}
