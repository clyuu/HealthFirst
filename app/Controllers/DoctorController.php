<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Hospital;
use App\Models\Incident;
use App\Models\MedicalDocument;
use App\Services\DoctorWorkflowService;
use App\Services\MapsService;
use Throwable;

final class DoctorController extends Controller
{
    private const LIVE_LOCATION_MAX_AGE_SECONDS = 180;

    public function dashboard(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);

        $this->render('doctor/dashboard', [
            'title' => 'Doctor Dashboard',
            'hospital' => $hospital,
            'incidents' => $hospital ? $this->presentIncidents((new Incident())->listDoctorFeed((int) $hospital['hospital_id'])) : [],
        ]);
    }

    public function admit(string|int $incidentId): void
    {
        $this->validateCsrf();
        $incidentId = (int) $incidentId;

        try {
            $user = Auth::user();
            $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);
            $caseId = (new DoctorWorkflowService())->admit($incidentId, (int) $user['user_id'], (int) $hospital['hospital_id']);
            Flash::success('Patient admitted and assigned to you.');
            $this->json(['case_id' => $caseId]);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function myPatients(): void
    {
        $patients = (new Incident())->listMyPatients((int) Auth::id());
        $documents = [];
        foreach ($patients as $patient) {
            $documents[(int) $patient['user_id']] = (new MedicalDocument())->listPatientUploadsByUserId((int) $patient['user_id']);
        }

        $this->render('doctor/patients', [
            'title' => 'My Patients',
            'patients' => $patients,
            'documents' => $documents,
        ]);
    }

    public function discharge(string|int $caseId): void
    {
        $this->validateCsrf();
        $caseId = (int) $caseId;

        try {
            (new DoctorWorkflowService())->discharge($caseId, (int) Auth::id());
            Flash::success('Patient discharged successfully.');
        } catch (Throwable $exception) {
            Flash::error('Unable to discharge patient: ' . $exception->getMessage());
        }

        $this->redirect('/doctor/patients');
    }

    private function presentIncidents(array $incidents): array
    {
        $maps = new MapsService();

        return array_map(function (array $incident) use ($maps): array {
            $startedAt = $incident['picked_up_at'] ?? null;
            $storedEtaSeconds = (int) ($incident['hospital_eta_seconds'] ?? 0);
            $origin = $this->freshAmbulanceOrigin($incident, $startedAt)
                ?? $this->fallbackAmbulanceOrigin($incident);

            if ($origin !== null) {
                $route = $maps->computeRoute(
                    $origin['lat'],
                    $origin['lng'],
                    (float) ($incident['hospital_latitude'] ?? 0),
                    (float) ($incident['hospital_longitude'] ?? 0)
                );
                $liveRemaining = eta_remaining_seconds((int) ($route['eta_seconds'] ?? 0), (string) ($origin['recorded_at'] ?? ''));
                $incident['display_eta_seconds'] = $origin['fresh']
                    ? $liveRemaining
                    : eta_remaining_seconds($storedEtaSeconds ?: (int) ($route['eta_seconds'] ?? 0), $startedAt);
                $incident['display_distance_meters'] = (int) ($route['distance_meters'] ?? 0);
                $incident['eta_live'] = true;
                return $incident;
            }

            $incident['display_eta_seconds'] = eta_remaining_seconds($storedEtaSeconds, $startedAt);
            $incident['display_distance_meters'] = 0;
            $incident['eta_live'] = $startedAt !== null && $startedAt !== '';
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

    private function fallbackAmbulanceOrigin(array $incident): ?array
    {
        $lat = (float) ($incident['ambulance_latitude'] ?? 0);
        $lng = (float) ($incident['ambulance_longitude'] ?? 0);
        if ($lat === 0.0 || $lng === 0.0) {
            $lat = (float) ($incident['incident_latitude'] ?? 0);
            $lng = (float) ($incident['incident_longitude'] ?? 0);
        }

        if ($lat === 0.0 || $lng === 0.0) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng, 'fresh' => false];
    }
}
