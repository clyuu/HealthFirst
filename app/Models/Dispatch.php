<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Dispatch extends Model
{
    public function createInitial(int $incidentId, int $hospitalId, ?int $sceneEtaSeconds = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO dispatch (
                incident_id, hospital_id, dispatch_status, scene_eta_seconds
             ) VALUES (
                :incident_id, :hospital_id, "unassigned", :scene_eta_seconds
             )
             ON DUPLICATE KEY UPDATE
                hospital_id = VALUES(hospital_id),
                scene_eta_seconds = VALUES(scene_eta_seconds)'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'hospital_id' => $hospitalId,
            'scene_eta_seconds' => $sceneEtaSeconds,
        ]);
    }

    public function assignAmbulance(int $incidentId, int $hospitalId, int $ambulanceId, int $assignedByUserId, ?int $sceneEtaSeconds = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE dispatch
             SET hospital_id = :hospital_id,
                 ambulance_id = :ambulance_id,
                 assigned_by_user_id = :assigned_by_user_id,
                 dispatch_status = "ambulance_assigned",
                 assigned_at = CURRENT_TIMESTAMP,
                 scene_eta_seconds = :scene_eta_seconds
             WHERE incident_id = :incident_id'
        );
        $stmt->execute([
            'hospital_id' => $hospitalId,
            'ambulance_id' => $ambulanceId,
            'assigned_by_user_id' => $assignedByUserId,
            'scene_eta_seconds' => $sceneEtaSeconds,
            'incident_id' => $incidentId,
        ]);
    }

    public function markPickup(int $incidentId, ?int $hospitalEtaSeconds = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE dispatch
             SET picked_up_at = CURRENT_TIMESTAMP,
                 dispatch_status = "patient_picked_up",
                 hospital_eta_seconds = :hospital_eta_seconds
             WHERE incident_id = :incident_id'
        );
        $stmt->execute([
            'hospital_eta_seconds' => $hospitalEtaSeconds,
            'incident_id' => $incidentId,
        ]);
    }

    public function markAdmitted(int $incidentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE dispatch
             SET arrived_hospital_at = COALESCE(arrived_hospital_at, CURRENT_TIMESTAMP),
                 dispatch_status = "admitted"
             WHERE incident_id = :incident_id'
        );
        $stmt->execute(['incident_id' => $incidentId]);
    }

    public function markArrivedAtHospital(int $incidentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE dispatch
             SET arrived_hospital_at = COALESCE(arrived_hospital_at, CURRENT_TIMESTAMP),
                 dispatch_status = "en_route_hospital"
             WHERE incident_id = :incident_id'
        );
        $stmt->execute(['incident_id' => $incidentId]);
    }

    public function markDischarged(int $incidentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE dispatch
             SET dispatch_status = "discharged"
             WHERE incident_id = :incident_id'
        );
        $stmt->execute(['incident_id' => $incidentId]);
    }
}
