<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ambulance;
use App\Models\Dispatch;
use App\Models\Incident;
use RuntimeException;

final class HospitalWorkflowService
{
    public function assignAmbulance(int $incidentId, int $ambulanceId, int $assignedByUserId): array
    {
        $incidentModel = new Incident();
        $incident = $incidentModel->findDetailedById($incidentId);
        $ambulance = (new Ambulance())->findById($ambulanceId);

        if ($incident === null || $ambulance === null) {
            throw new RuntimeException('Incident or ambulance not found.');
        }

        if ((int) $incident['selected_hospital_id'] !== (int) $ambulance['hospital_id']) {
            throw new RuntimeException('Selected ambulance does not belong to this hospital.');
        }

        if (($ambulance['status'] ?? '') !== 'available') {
            throw new RuntimeException('Selected ambulance is no longer available.');
        }

        $originLat = $ambulance['current_latitude'] ?: $incident['hospital_latitude'];
        $originLng = $ambulance['current_longitude'] ?: $incident['hospital_longitude'];

        $route = (new MapsService())->computeRoute(
            (float) $originLat,
            (float) $originLng,
            (float) $incident['incident_latitude'],
            (float) $incident['incident_longitude']
        );

        (new Dispatch())->assignAmbulance(
            $incidentId,
            (int) $ambulance['hospital_id'],
            $ambulanceId,
            $assignedByUserId,
            $route['eta_seconds']
        );

        (new Ambulance())->updateStatus($ambulanceId, 'assigned');
        $incidentModel->setAmbulance($incidentId, $ambulanceId);
        $incidentModel->updateStatus($incidentId, 'ambulance_assigned', $assignedByUserId, 'Ambulance assigned from hospital dashboard.');

        return $incidentModel->findDetailedById($incidentId) ?? [];
    }
}
