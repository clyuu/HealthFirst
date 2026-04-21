<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class DoctorCaseAssignment extends Model
{
    public function admit(int $incidentId, int $doctorUserId, int $hospitalId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO doctor_case_assignments (
                incident_id, doctor_user_id, hospital_id, status, admitted_at
             ) VALUES (
                :incident_id, :doctor_user_id, :hospital_id, "admitted", CURRENT_TIMESTAMP
             )'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'doctor_user_id' => $doctorUserId,
            'hospital_id' => $hospitalId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function discharge(int $caseId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE doctor_case_assignments
             SET status = "discharged", discharged_at = CURRENT_TIMESTAMP
             WHERE case_assignment_id = :case_id'
        );
        $stmt->execute(['case_id' => $caseId]);
    }

    public function findById(int $caseId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dca.*, i.user_id
             FROM doctor_case_assignments dca
             INNER JOIN accident_incidents i ON i.incident_id = dca.incident_id
             WHERE dca.case_assignment_id = :case_id
             LIMIT 1'
        );
        $stmt->execute(['case_id' => $caseId]);
        return $stmt->fetch() ?: null;
    }

    public function doctorHasAccessToUser(int $doctorUserId, int $patientUserId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM doctor_case_assignments dca
             INNER JOIN accident_incidents i ON i.incident_id = dca.incident_id
             WHERE dca.doctor_user_id = :doctor_user_id
               AND i.user_id = :patient_user_id
               AND dca.status = "admitted"'
        );
        $stmt->execute([
            'doctor_user_id' => $doctorUserId,
            'patient_user_id' => $patientUserId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
