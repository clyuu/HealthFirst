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
use App\Services\MapsService;
use Throwable;

final class AmbulanceController extends Controller
{
    private const LIVE_LOCATION_MAX_AGE_SECONDS = 180;

    public function paramedicDashboard(): void
    {
        $paramedic = Auth::user();
        $ambulance = (new Ambulance())->findByParamedicUserId((int) $paramedic['user_id']);
        $incidents = $this->presentIncidents((new Incident())->listAmbulanceQueue((int) $paramedic['user_id']));

        $this->render('paramedic/dashboard', [
            'title' => 'Paramedic Dashboard',
            'ambulance' => $ambulance,
            'incidents' => $incidents,
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function ambulanceDashboard(): void
    {
        $paramedic = Auth::user();
        $ambulance = (new Ambulance())->findByParamedicUserId((int) $paramedic['user_id']);
        $incidents = $this->presentIncidents((new Incident())->listAmbulanceQueue((int) $paramedic['user_id']));

        $this->render('ambulance/dashboard', [
            'title' => 'Ambulance Operations',
            'ambulance' => $ambulance,
            'incidents' => $incidents,
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function incidents(): void
    {
        $this->json([
            'incidents' => $this->presentIncidents((new Incident())->listAmbulanceQueue((int) Auth::id())),
        ]);
    }

    public function navigation(string|int $incidentId): void
    {
        $incidentId = (int) $incidentId;
        $paramedic = Auth::user();
        $ambulance = (new Ambulance())->findByParamedicUserId((int) $paramedic['user_id']);
        $incident = (new Incident())->findDetailedById($incidentId);

        if ($ambulance === null || $incident === null || (int) ($incident['ambulance_id'] ?? 0) !== (int) $ambulance['ambulance_id']) {
            $this->json(['error' => 'Navigation context could not be resolved for this ambulance.'], 404);
        }

        $toHospital = in_array((string) ($incident['status'] ?? ''), ['patient_picked_up', 'en_route_hospital'], true);
        $originLat = isset($_GET['latitude']) ? (float) $_GET['latitude'] : (float) ($ambulance['current_latitude'] ?? 0);
        $originLng = isset($_GET['longitude']) ? (float) $_GET['longitude'] : (float) ($ambulance['current_longitude'] ?? 0);

        if ($originLat === 0.0 || $originLng === 0.0) {
            $originLat = (float) ($incident['ambulance_latitude'] ?? $incident['hospital_latitude'] ?? 0);
            $originLng = (float) ($incident['ambulance_longitude'] ?? $incident['hospital_longitude'] ?? 0);
        }

        $destinationLat = $toHospital
            ? (float) ($incident['hospital_latitude'] ?? 0)
            : (float) ($incident['incident_latitude'] ?? 0);
        $destinationLng = $toHospital
            ? (float) ($incident['hospital_longitude'] ?? 0)
            : (float) ($incident['incident_longitude'] ?? 0);

        if ($originLat === 0.0 || $originLng === 0.0 || $destinationLat === 0.0 || $destinationLng === 0.0) {
            $this->json(['error' => 'Navigation route coordinates are incomplete.'], 422);
        }

        $route = (new MapsService())->computeRoute($originLat, $originLng, $destinationLat, $destinationLng);
        $instruction = $toHospital
            ? 'Drive to ' . ((string) ($incident['hospital_name'] ?? 'Hospital'))
            : 'Drive to the accident scene';

        $this->json([
            'incident_id' => $incidentId,
            'origin_latitude' => $originLat,
            'origin_longitude' => $originLng,
            'destination_latitude' => $destinationLat,
            'destination_longitude' => $destinationLng,
            'destination_label' => $toHospital
                ? (string) ($incident['hospital_name'] ?? 'Hospital')
                : 'Accident Scene',
            'patient_name' => (string) ($incident['patient_name'] ?? 'Active case'),
            'ambulance_number' => (string) ($ambulance['ambulance_number'] ?? 'Ambulance'),
            'instruction' => $instruction,
            'eta_seconds' => (int) ($route['eta_seconds'] ?? 0),
            'distance_meters' => (int) ($route['distance_meters'] ?? 0),
            'encoded_polyline' => $route['encoded_polyline'] ?? null,
        ]);
    }

    public function lookupPatient(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
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

    public function updateLocation(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
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

    public function pickupPatient(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
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

    public function arriveAtHospital(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
        try {
            $this->json((new AmbulanceWorkflowService())->arriveAtHospital((int) Auth::id(), $incidentId));
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function saveVitals(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
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

    public function startInjurySession(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;
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

    public function analyzeImage(string|int $sessionId): void
    {
        $this->validateCsrf();
        $sessionId = (int) $sessionId;
        try {
            $result = (new AmbulanceWorkflowService())->analyzeImage($sessionId, $_FILES['injury_photo']);
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function finalizeSession(string|int $sessionId): void
    {
        $this->validateCsrf();
        $sessionId = (int) $sessionId;
        try {
            $result = (new AmbulanceWorkflowService())->finalizeSession($sessionId, [
                'special_note' => trim((string) ($_POST['special_note'] ?? '')),
            ]);
            $this->json($result);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    private function presentIncidents(array $incidents): array
    {
        $maps = new MapsService();

        return array_map(function (array $incident) use ($maps): array {
            $status = (string) ($incident['status'] ?? '');
            $toHospital = in_array($status, ['patient_picked_up', 'en_route_hospital'], true);
            $startedAt = $toHospital
                ? ($incident['picked_up_at'] ?? null)
                : ($incident['assigned_at'] ?? $incident['dispatch_created_at'] ?? null);
            $storedEtaSeconds = $toHospital
                ? (int) ($incident['hospital_eta_seconds'] ?? 0)
                : (int) ($incident['scene_eta_seconds'] ?? 0);
            $origin = $this->freshAmbulanceOrigin($incident, $startedAt)
                ?? $this->fallbackAmbulanceOrigin($incident, $toHospital);
            $destinationLat = $toHospital
                ? (float) ($incident['hospital_latitude'] ?? 0)
                : (float) ($incident['incident_latitude'] ?? 0);
            $destinationLng = $toHospital
                ? (float) ($incident['hospital_longitude'] ?? 0)
                : (float) ($incident['incident_longitude'] ?? 0);

            if ($origin !== null && $destinationLat !== 0.0 && $destinationLng !== 0.0) {
                $route = $maps->computeRoute($origin['lat'], $origin['lng'], $destinationLat, $destinationLng);
                $liveRemaining = eta_remaining_seconds((int) ($route['eta_seconds'] ?? 0), (string) ($origin['recorded_at'] ?? ''));
                $incident['display_eta_seconds'] = $origin['fresh']
                    ? $liveRemaining
                    : eta_remaining_seconds($storedEtaSeconds ?: (int) ($route['eta_seconds'] ?? 0), $startedAt);
                $incident['display_distance_meters'] = (int) ($route['distance_meters'] ?? 0);
                $incident['display_route_polyline'] = $route['encoded_polyline'] ?? null;
                $incident['display_origin_latitude'] = $origin['lat'];
                $incident['display_origin_longitude'] = $origin['lng'];
                $incident['display_destination_latitude'] = $destinationLat;
                $incident['display_destination_longitude'] = $destinationLng;
                $incident['eta_live'] = true;
            } else {
                $incident['display_eta_seconds'] = eta_remaining_seconds($storedEtaSeconds, $startedAt);
                $incident['display_distance_meters'] = 0;
                $incident['display_route_polyline'] = null;
                $incident['display_origin_latitude'] = null;
                $incident['display_origin_longitude'] = null;
                $incident['display_destination_latitude'] = $destinationLat ?: null;
                $incident['display_destination_longitude'] = $destinationLng ?: null;
                $incident['eta_live'] = false;
            }

            $incident['nav_target_label'] = $toHospital
                ? (string) ($incident['hospital_name'] ?? 'Hospital')
                : 'Accident Scene';

            return $incident;
        }, $incidents);
    }

    private function freshAmbulanceOrigin(array $incident, ?string $startedAt): ?array
    {
        $lat = (float) ($incident['live_ambulance_latitude'] ?? 0);
        $lng = (float) ($incident['live_ambulance_longitude'] ?? 0);
        $recordedAt = (string) ($incident['live_ambulance_recorded_at'] ?? '');
        if ($lat === 0.0 || $lng === 0.0 || $recordedAt === '') {
            return null;
        }

        $recordedAtUnix = strtotime($recordedAt);
        if ($recordedAtUnix === false || time() - $recordedAtUnix > self::LIVE_LOCATION_MAX_AGE_SECONDS) {
            return null;
        }

        if ($startedAt !== null && $startedAt !== '') {
            $startedAtUnix = strtotime($startedAt);
            if ($startedAtUnix !== false && $recordedAtUnix + 5 < $startedAtUnix) {
                return null;
            }
        }

        return ['lat' => $lat, 'lng' => $lng, 'fresh' => true, 'recorded_at' => $recordedAt];
    }

    private function fallbackAmbulanceOrigin(array $incident, bool $toHospital): ?array
    {
        $lat = (float) ($incident['ambulance_latitude'] ?? 0);
        $lng = (float) ($incident['ambulance_longitude'] ?? 0);
        if ($lat === 0.0 || $lng === 0.0) {
            $lat = $toHospital
                ? (float) ($incident['incident_latitude'] ?? 0)
                : (float) ($incident['hospital_latitude'] ?? 0);
            $lng = $toHospital
                ? (float) ($incident['incident_longitude'] ?? 0)
                : (float) ($incident['hospital_longitude'] ?? 0);
        }

        if ($lat === 0.0 || $lng === 0.0) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng, 'fresh' => false];
    }
}
