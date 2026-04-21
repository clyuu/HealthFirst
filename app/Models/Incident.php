<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Incident extends Model
{
    private function baseSelect(): string
    {
        return 'SELECT i.*,
                       u.full_name AS patient_name,
                       u.nic_number,
                       u.phone AS patient_phone,
                       u.address AS patient_address,
                       u.date_of_birth,
                       mp.blood_group,
                       mp.allergies,
                       mp.chronic_conditions,
                       mp.notes AS medical_notes,
                       mp.emergency_phone,
                       h.hospital_name,
                       h.address AS hospital_address,
                       h.latitude AS hospital_latitude,
                       h.longitude AS hospital_longitude,
                       a.ambulance_number,
                       a.current_latitude AS ambulance_latitude,
                       a.current_longitude AS ambulance_longitude,
                       d.dispatch_id,
                       d.dispatch_status,
                       d.scene_eta_seconds,
                       d.hospital_eta_seconds,
                       d.ambulance_id,
                       av.result AS verification_result,
                       av.confidence_score AS verification_confidence,
                       ins.overall_severity,
                       ins.report_file_path,
                       ins.special_note,
                       doc.case_assignment_id,
                       doc.doctor_user_id,
                       du.full_name AS doctor_name
                FROM accident_incidents i
                INNER JOIN users u ON u.user_id = i.user_id
                LEFT JOIN medical_profiles mp ON mp.user_id = u.user_id
                LEFT JOIN hospitals h ON h.hospital_id = i.selected_hospital_id
                LEFT JOIN dispatch d ON d.incident_id = i.incident_id
                LEFT JOIN ambulances a ON a.ambulance_id = d.ambulance_id
                LEFT JOIN accident_verifications av ON av.incident_id = i.incident_id
                LEFT JOIN injury_sessions ins ON ins.session_id = (
                    SELECT session_id
                    FROM injury_sessions x
                    WHERE x.incident_id = i.incident_id
                    ORDER BY x.session_id DESC
                    LIMIT 1
                )
                LEFT JOIN doctor_case_assignments doc ON doc.case_assignment_id = (
                    SELECT case_assignment_id
                    FROM doctor_case_assignments y
                    WHERE y.incident_id = i.incident_id
                    ORDER BY y.case_assignment_id DESC
                    LIMIT 1
                )
                LEFT JOIN users du ON du.user_id = doc.doctor_user_id';
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO accident_incidents (
                user_id, qr_id, incident_latitude, incident_longitude,
                injured_count, status, public_message
             ) VALUES (
                :user_id, :qr_id, :incident_latitude, :incident_longitude,
                :injured_count, :status, :public_message
             )'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'qr_id' => $data['qr_id'],
            'incident_latitude' => $data['incident_latitude'],
            'incident_longitude' => $data['incident_longitude'],
            'injured_count' => $data['injured_count'] ?? 1,
            'status' => $data['status'] ?? 'reported',
            'public_message' => $data['public_message'] ?? null,
        ]);

        $incidentId = (int) $this->db->lastInsertId();
        $this->addStatusHistory($incidentId, $data['status'] ?? 'reported', null, 'Incident created from public QR scan.');
        return $incidentId;
    }

    public function attachScenePhoto(int $incidentId, string $imagePath): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO scene_photos (incident_id, image_path) VALUES (:incident_id, :image_path)'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'image_path' => $imagePath,
        ]);
    }

    public function setHospital(int $incidentId, int $hospitalId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accident_incidents
             SET selected_hospital_id = :hospital_id
             WHERE incident_id = :incident_id'
        );
        $stmt->execute([
            'hospital_id' => $hospitalId,
            'incident_id' => $incidentId,
        ]);
    }

    public function setAmbulance(int $incidentId, int $ambulanceId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accident_incidents
             SET selected_ambulance_id = :ambulance_id
             WHERE incident_id = :incident_id'
        );
        $stmt->execute([
            'ambulance_id' => $ambulanceId,
            'incident_id' => $incidentId,
        ]);
    }

    public function updateStatus(int $incidentId, string $status, ?int $userId = null, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accident_incidents
             SET status = :status
             WHERE incident_id = :incident_id'
        );
        $stmt->execute([
            'status' => $status,
            'incident_id' => $incidentId,
        ]);

        $this->addStatusHistory($incidentId, $status, $userId, $note);
    }

    public function addStatusHistory(int $incidentId, string $status, ?int $userId, ?string $note = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO incident_status_history (
                incident_id, status, changed_by_user_id, note
             ) VALUES (
                :incident_id, :status, :changed_by_user_id, :note
             )'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'status' => $status,
            'changed_by_user_id' => $userId,
            'note' => $note,
        ]);
    }

    public function findDetailedById(int $incidentId): ?array
    {
        $stmt = $this->db->prepare($this->baseSelect() . ' WHERE i.incident_id = :incident_id LIMIT 1');
        $stmt->execute(['incident_id' => $incidentId]);
        return $stmt->fetch() ?: null;
    }

    public function listHospitalQueue(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             WHERE i.selected_hospital_id = :hospital_id
               AND i.status IN ("verified_unassigned", "ambulance_assigned", "en_route_scene", "patient_picked_up", "en_route_hospital")
             ORDER BY i.reported_at DESC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function listDischargedByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             WHERE i.selected_hospital_id = :hospital_id
               AND i.status = "discharged"
             ORDER BY i.reported_at DESC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function listAmbulanceQueue(int $paramedicUserId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             INNER JOIN ambulance_staff_assignments asa
               ON asa.ambulance_id = d.ambulance_id AND asa.is_active = 1
             WHERE asa.user_id = :user_id
               AND i.status IN ("ambulance_assigned", "en_route_scene", "patient_picked_up", "en_route_hospital")
             ORDER BY i.reported_at DESC'
        );
        $stmt->execute(['user_id' => $paramedicUserId]);
        return $stmt->fetchAll();
    }

    public function listDoctorFeed(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             WHERE i.selected_hospital_id = :hospital_id
               AND i.status IN ("patient_picked_up", "en_route_hospital")
             ORDER BY i.reported_at DESC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function listMyPatients(int $doctorUserId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             INNER JOIN doctor_case_assignments dca
               ON dca.incident_id = i.incident_id
             WHERE dca.doctor_user_id = :doctor_user_id
               AND dca.status = "admitted"
             ORDER BY dca.admitted_at DESC'
        );
        $stmt->execute(['doctor_user_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }
}

