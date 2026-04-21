<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Ambulance;
use App\Models\Hospital;
use App\Models\Incident;
use App\Services\HospitalWorkflowService;
use Throwable;

final class HospitalController extends Controller
{
    public function dashboard(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);

        $this->render('hospital/dashboard', [
            'title' => 'Hospital Dashboard',
            'hospital' => $hospital,
            'incidents' => $hospital ? (new Incident())->listHospitalQueue((int) $hospital['hospital_id']) : [],
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
        }

        $this->json([
            'incidents' => (new Incident())->listHospitalQueue((int) $hospital['hospital_id']),
            'discharged' => (new Incident())->listDischargedByHospital((int) $hospital['hospital_id']),
        ]);
    }

    public function assignAmbulance(int $incidentId): void
    {
        $this->validateCsrf();

        try {
            $incident = (new HospitalWorkflowService())->assignAmbulance(
                $incidentId,
                (int) $_POST['ambulance_id'],
                (int) Auth::id()
            );
            $this->json(['incident' => $incident, 'message' => 'Ambulance assigned successfully.']);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }
}

