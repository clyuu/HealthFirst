<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Ambulance;
use App\Models\Hospital;
use App\Models\Incident;
use App\Models\MedicalDocument;
use App\Services\DocumentService;
use App\Services\HospitalWorkflowService;
use App\Services\MapsService;
use RuntimeException;
use Throwable;

final class HospitalController extends Controller
{
    private const LIVE_LOCATION_MAX_AGE_SECONDS = 180;

    public function dashboard(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);
        $incidents = $hospital ? $this->presentIncidents((new Incident())->listHospitalQueue((int) $hospital['hospital_id'])) : [];

        $this->render('hospital/dashboard', [
            'title' => 'Hospital Dashboard',
            'hospital' => $hospital,
            'incidents' => $incidents,
            'arrivals' => $hospital ? (new Incident())->listArrivedAtHospitalByHospital((int) $hospital['hospital_id']) : [],
            'discharged' => $hospital ? (new Incident())->listDischargedByHospital((int) $hospital['hospital_id']) : [],
            'ambulances' => $hospital ? (new Ambulance())->availableByHospital((int) $hospital['hospital_id']) : [],
            'mapsApiKey' => (string) config_value('services.google_maps_api_key', ''),
        ]);
    }

    public function incidents(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);
        if ($hospital === null) {
            $this->json(['incidents' => []]);
            return;
        }

        $this->json([
            'incidents' => $this->presentIncidents((new Incident())->listHospitalQueue((int) $hospital['hospital_id'])),
            'arrivals' => (new Incident())->listArrivedAtHospitalByHospital((int) $hospital['hospital_id']),
            'discharged' => (new Incident())->listDischargedByHospital((int) $hospital['hospital_id']),
        ]);
    }

    public function assignAmbulance(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;

        try {
            $incident = (new HospitalWorkflowService())->assignAmbulance(
                $incidentId,
                (int) $_POST['ambulance_id'],
                (int) Auth::id()
            );
            $this->json([
                'incident' => $incident,
                'message' => sprintf(
                    'Incident #%d assigned to %s.',
                    (int) ($incident['incident_id'] ?? $incidentId),
                    (string) ($incident['ambulance_number'] ?? 'Ambulance')
                ),
            ]);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function uploadDischargeDocument(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;

        try {
            $user = Auth::user();
            $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);
            $incident = (new Incident())->findDetailedById($incidentId);

            if ($hospital === null || $incident === null) {
                throw new RuntimeException('Hospital incident not found.');
            }

            if ((int) ($incident['selected_hospital_id'] ?? 0) !== (int) $hospital['hospital_id']) {
                throw new RuntimeException('This patient does not belong to your hospital queue.');
            }

            if (($incident['status'] ?? '') !== 'discharged') {
                throw new RuntimeException('Hospital documents can be added after doctor discharge only.');
            }

            if (empty($incident['user_id'])) {
                throw new RuntimeException('This incident is not linked to a registered patient account.');
            }

            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                $title = 'Hospital discharge document';
            }

            $files = $this->uploadedDocumentFiles($_FILES['documents'] ?? []);
            if ($files === []) {
                throw new RuntimeException('Please choose at least one document.');
            }

            $documentService = new DocumentService();
            $documentModel = new MedicalDocument();
            foreach ($files as $file) {
                $stored = $documentService->storeUploadedFile($file, 'documents');
                $documentModel->create([
                    'user_id' => (int) $incident['user_id'],
                    'incident_id' => $incidentId,
                    'hospital_id' => (int) $hospital['hospital_id'],
                    'uploaded_by_user_id' => (int) $user['user_id'],
                    'document_category' => 'discharge_or_treatment',
                    'source_type' => 'hospital_upload',
                    'title' => $this->hospitalDocumentTitle($title, $file, count($files)),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'file_path' => $stored['file_path'],
                    'mime_type' => $stored['mime_type'],
                    'file_size' => $stored['file_size'],
                ]);
            }

            Flash::success('Hospital documents uploaded. Patient cleared from the discharged list.');
        } catch (Throwable $exception) {
            Flash::error('Unable to upload hospital document: ' . $exception->getMessage());
        }

        $this->redirect('/hospital/dashboard#discharged-patients');
    }

    private function uploadedDocumentFiles(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$files];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            $file = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = $file;
        }

        return $normalized;
    }

    private function hospitalDocumentTitle(string $baseTitle, array $file, int $fileCount): string
    {
        if ($fileCount <= 1) {
            return $baseTitle;
        }

        $filename = pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME);
        return $filename === '' ? $baseTitle : $baseTitle . ' - ' . $filename;
    }

    private function presentIncidents(array $incidents): array
    {
        $maps = new MapsService();

        return array_map(function (array $incident) use ($maps): array {
            $status = (string) ($incident['status'] ?? '');
            $isHospitalLeg = in_array($status, ['patient_picked_up', 'en_route_hospital'], true);
            $isSceneCountdown = in_array($status, ['ambulance_assigned', 'en_route_scene'], true);

            if ($status === 'verified_unassigned') {
                $route = $maps->computeRoute(
                    (float) ($incident['hospital_latitude'] ?? 0),
                    (float) ($incident['hospital_longitude'] ?? 0),
                    (float) ($incident['incident_latitude'] ?? 0),
                    (float) ($incident['incident_longitude'] ?? 0)
                );
                $startedAt = $incident['dispatch_created_at'] ?? null;
                $etaSeconds = (int) ($incident['scene_eta_seconds'] ?? $route['eta_seconds'] ?? 0);

                $incident['display_eta_seconds'] = eta_remaining_seconds($etaSeconds, $startedAt);
                $incident['display_distance_meters'] = (int) ($route['distance_meters'] ?? 0);
                $incident['display_route_polyline'] = $route['encoded_polyline'] ?? null;
                $incident['display_origin_latitude'] = $incident['hospital_latitude'] ?? null;
                $incident['display_origin_longitude'] = $incident['hospital_longitude'] ?? null;
                $incident['display_destination_latitude'] = $incident['incident_latitude'] ?? null;
                $incident['display_destination_longitude'] = $incident['incident_longitude'] ?? null;
                $incident['display_destination_label'] = 'accident scene';
                $incident['eta_live'] = $startedAt !== null && $startedAt !== '' && $etaSeconds > 0;
                return $incident;
            }

            if ($isHospitalLeg) {
                $startedAt = $incident['picked_up_at'] ?? null;
                $storedEtaSeconds = (int) ($incident['hospital_eta_seconds'] ?? 0);
                $origin = $this->freshAmbulanceOrigin($incident, $startedAt)
                    ?? $this->fallbackAmbulanceOrigin($incident, true);
                $route = $origin !== null ? $maps->computeRoute(
                    $origin['lat'],
                    $origin['lng'],
                    (float) ($incident['hospital_latitude'] ?? 0),
                    (float) ($incident['hospital_longitude'] ?? 0)
                ) : ['eta_seconds' => 0, 'distance_meters' => 0, 'encoded_polyline' => null];
                $liveRemaining = eta_remaining_seconds((int) ($route['eta_seconds'] ?? 0), (string) ($origin['recorded_at'] ?? ''));
                $incident['display_eta_seconds'] = $origin !== null && $origin['fresh']
                    ? $liveRemaining
                    : eta_remaining_seconds($storedEtaSeconds ?: (int) ($route['eta_seconds'] ?? 0), $startedAt);
                $incident['display_distance_meters'] = (int) ($route['distance_meters'] ?? 0);
                $incident['display_route_polyline'] = $route['encoded_polyline'] ?? null;
                $incident['display_origin_latitude'] = $origin['lat'] ?? null;
                $incident['display_origin_longitude'] = $origin['lng'] ?? null;
                $incident['display_destination_latitude'] = $incident['hospital_latitude'] ?? null;
                $incident['display_destination_longitude'] = $incident['hospital_longitude'] ?? null;
                $incident['display_destination_label'] = 'hospital';
                $incident['eta_live'] = true;
                return $incident;
            }

            if ($isSceneCountdown) {
                $startedAt = $incident['assigned_at'] ?? $incident['dispatch_created_at'] ?? null;
                $storedSceneEtaSeconds = (int) ($incident['scene_eta_seconds'] ?? 0);
                $origin = $this->freshAmbulanceOrigin($incident, $startedAt)
                    ?? $this->fallbackAmbulanceOrigin($incident, false);
                $route = $origin !== null ? $maps->computeRoute(
                    $origin['lat'],
                    $origin['lng'],
                    (float) ($incident['incident_latitude'] ?? 0),
                    (float) ($incident['incident_longitude'] ?? 0)
                ) : ['eta_seconds' => 0, 'distance_meters' => 0, 'encoded_polyline' => null];
                $liveRemaining = eta_remaining_seconds((int) ($route['eta_seconds'] ?? 0), (string) ($origin['recorded_at'] ?? ''));
                $incident['display_eta_seconds'] = $origin !== null && $origin['fresh']
                    ? $liveRemaining
                    : eta_remaining_seconds($storedSceneEtaSeconds ?: (int) ($route['eta_seconds'] ?? 0), $startedAt);
                $incident['display_distance_meters'] = (int) ($route['distance_meters'] ?? 0);
                $incident['display_route_polyline'] = $route['encoded_polyline'] ?? null;
                $incident['display_origin_latitude'] = $origin['lat'] ?? null;
                $incident['display_origin_longitude'] = $origin['lng'] ?? null;
                $incident['display_destination_latitude'] = $incident['incident_latitude'] ?? null;
                $incident['display_destination_longitude'] = $incident['incident_longitude'] ?? null;
                $incident['display_destination_label'] = 'accident scene';
                $incident['eta_live'] = true;
                return $incident;
            }

            $incident['display_eta_seconds'] = (int) ($incident['scene_eta_seconds'] ?? 0);
            $incident['display_distance_meters'] = null;
            $incident['display_route_polyline'] = null;
            $incident['display_origin_latitude'] = null;
            $incident['display_origin_longitude'] = null;
            $incident['display_destination_latitude'] = null;
            $incident['display_destination_longitude'] = null;
            $incident['display_destination_label'] = null;
            $incident['eta_live'] = false;
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
