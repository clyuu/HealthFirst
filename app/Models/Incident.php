<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Incident extends Model
{
    private function baseSelect(): string
    {
        return 'SELECT i.*,
                       COALESCE(NULLIF(u.full_name, ""), NULLIF(i.reported_person_name, ""), "Unidentified Patient") AS patient_name,
                       COALESCE(NULLIF(u.nic_number, ""), NULLIF(i.reported_person_nic, "")) AS nic_number,
                       COALESCE(NULLIF(u.phone, ""), NULLIF(i.reported_person_phone, "")) AS patient_phone,
                       u.address AS patient_address,
                       u.date_of_birth,
                       i.reported_person_name,
                       i.reported_person_nic,
                       i.reported_person_phone,
                       i.reported_vehicle_number,
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
                       al.latitude AS live_ambulance_latitude,
                       al.longitude AS live_ambulance_longitude,
                       al.recorded_at AS live_ambulance_recorded_at,
                       d.dispatch_id,
                       d.dispatch_status,
                       d.scene_eta_seconds,
                       d.hospital_eta_seconds,
                       d.ambulance_id,
                       d.created_at AS dispatch_created_at,
                       d.assigned_at,
                       d.picked_up_at,
                       d.left_scene_at,
                       d.arrived_hospital_at,
                       av.result AS verification_result,
                       av.confidence_score AS verification_confidence,
                       ins.overall_severity,
                       ins.report_file_path,
                       ins.special_note,
                       doc.case_assignment_id,
                       doc.doctor_user_id,
                       du.full_name AS doctor_name
                FROM accident_incidents i
                LEFT JOIN users u ON u.user_id = i.user_id
                LEFT JOIN medical_profiles mp ON mp.user_id = u.user_id
                LEFT JOIN hospitals h ON h.hospital_id = i.selected_hospital_id
                LEFT JOIN dispatch d ON d.incident_id = i.incident_id
                LEFT JOIN ambulances a ON a.ambulance_id = d.ambulance_id
                LEFT JOIN ambulance_locations al ON al.location_id = (
                    SELECT location_id
                    FROM ambulance_locations z
                    WHERE z.incident_id = i.incident_id
                      AND z.ambulance_id = d.ambulance_id
                    ORDER BY z.recorded_at DESC
                    LIMIT 1
                )
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
                injured_count, status, public_message,
                reported_person_name, reported_person_nic, reported_person_phone, reported_vehicle_number
             ) VALUES (
                :user_id, :qr_id, :incident_latitude, :incident_longitude,
                :injured_count, :status, :public_message,
                :reported_person_name, :reported_person_nic, :reported_person_phone, :reported_vehicle_number
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
            'reported_person_name' => $data['reported_person_name'] ?? null,
            'reported_person_nic' => $data['reported_person_nic'] ?? null,
            'reported_person_phone' => $data['reported_person_phone'] ?? null,
            'reported_vehicle_number' => $data['reported_vehicle_number'] ?? null,
        ]);

        $incidentId = (int) $this->db->lastInsertId();
        $this->addStatusHistory($incidentId, $data['status'] ?? 'reported', null, $data['source_note'] ?? 'Incident created from public emergency report.');
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

    public function findRecentActiveDuplicate(array $criteria, int $withinMinutes = 30): ?array
    {
        $identityClauses = [];
        $params = [
            'lat_min' => ((float) ($criteria['incident_latitude'] ?? 0)) - 0.001,
            'lat_max' => ((float) ($criteria['incident_latitude'] ?? 0)) + 0.001,
            'lng_min' => ((float) ($criteria['incident_longitude'] ?? 0)) - 0.001,
            'lng_max' => ((float) ($criteria['incident_longitude'] ?? 0)) + 0.001,
        ];

        if (!empty($criteria['qr_id'])) {
            $identityClauses[] = 'i.qr_id = :qr_id';
            $params['qr_id'] = (int) $criteria['qr_id'];
        }

        if (!empty($criteria['user_id'])) {
            $identityClauses[] = 'i.user_id = :user_id';
            $params['user_id'] = (int) $criteria['user_id'];
        }

        if (!empty($criteria['reported_person_nic'])) {
            $identityClauses[] = 'i.reported_person_nic = :reported_person_nic';
            $params['reported_person_nic'] = (string) $criteria['reported_person_nic'];
        }

        if (!empty($criteria['reported_person_phone'])) {
            $identityClauses[] = 'i.reported_person_phone = :reported_person_phone';
            $params['reported_person_phone'] = (string) $criteria['reported_person_phone'];
        }

        if (!empty($criteria['reported_vehicle_number'])) {
            $identityClauses[] = 'i.reported_vehicle_number = :reported_vehicle_number';
            $params['reported_vehicle_number'] = (string) $criteria['reported_vehicle_number'];
        }

        if (!empty($criteria['reported_person_name'])) {
            $identityClauses[] = 'i.reported_person_name = :reported_person_name';
            $params['reported_person_name'] = (string) $criteria['reported_person_name'];
        }

        if ($identityClauses === []) {
            return null;
        }

        $withinMinutes = max($withinMinutes, 1);
        $sql = $this->baseSelect() . '
             WHERE i.status IN ("reported", "verified_unassigned", "ambulance_assigned", "en_route_scene", "patient_picked_up", "en_route_hospital")
               AND i.reported_at >= DATE_SUB(NOW(), INTERVAL ' . $withinMinutes . ' MINUTE)
               AND i.incident_latitude BETWEEN :lat_min AND :lat_max
               AND i.incident_longitude BETWEEN :lng_min AND :lng_max
               AND (' . implode(' OR ', $identityClauses) . ')
             ORDER BY i.reported_at DESC
             LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
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
               AND d.arrived_hospital_at IS NULL
             ORDER BY CASE i.status
                        WHEN "verified_unassigned" THEN 1
                        WHEN "ambulance_assigned" THEN 2
                        WHEN "en_route_scene" THEN 3
                        WHEN "patient_picked_up" THEN 4
                        WHEN "en_route_hospital" THEN 5
                        ELSE 6
                      END ASC,
                      i.reported_at DESC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function listArrivedAtHospitalByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            $this->baseSelect() . '
             WHERE i.selected_hospital_id = :hospital_id
               AND d.arrived_hospital_at IS NOT NULL
               AND i.status IN ("en_route_hospital", "admitted")
             ORDER BY d.arrived_hospital_at DESC'
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
               AND NOT EXISTS (
                   SELECT 1
                   FROM medical_documents md
                   WHERE md.incident_id = i.incident_id
                     AND md.hospital_id = :document_hospital_id
                     AND md.source_type = "hospital_upload"
                     AND md.document_category = "discharge_or_treatment"
               )
             ORDER BY i.reported_at DESC'
        );
        $stmt->execute([
            'hospital_id' => $hospitalId,
            'document_hospital_id' => $hospitalId,
        ]);
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
               AND d.arrived_hospital_at IS NULL
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
