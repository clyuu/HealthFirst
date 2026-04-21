<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Ambulance extends Model
{
    public function listByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS assigned_paramedic
             FROM ambulances a
             LEFT JOIN ambulance_staff_assignments asa
               ON asa.ambulance_id = a.ambulance_id AND asa.is_active = 1
             LEFT JOIN users u ON u.user_id = asa.user_id
             WHERE a.hospital_id = :hospital_id
             ORDER BY a.ambulance_number ASC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function availableByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ambulances
             WHERE hospital_id = :hospital_id AND status = "available"
             ORDER BY ambulance_number ASC'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function findByParamedicUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, h.hospital_name
             FROM ambulance_staff_assignments asa
             INNER JOIN ambulances a ON a.ambulance_id = asa.ambulance_id
             INNER JOIN hospitals h ON h.hospital_id = a.hospital_id
             WHERE asa.user_id = :user_id AND asa.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $ambulanceId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ambulances WHERE ambulance_id = :ambulance_id LIMIT 1');
        $stmt->execute(['ambulance_id' => $ambulanceId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ambulances (
                hospital_id, ambulance_number, status, capacity_stretchers
             ) VALUES (
                :hospital_id, :ambulance_number, :status, :capacity_stretchers
             )'
        );
        $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'ambulance_number' => $data['ambulance_number'],
            'status' => $data['status'] ?? 'available',
            'capacity_stretchers' => $data['capacity_stretchers'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function assignStaff(int $ambulanceId, int $userId): void
    {
        $this->db->prepare(
            'UPDATE ambulance_staff_assignments
             SET is_active = 0
             WHERE ambulance_id = :ambulance_id'
        )->execute(['ambulance_id' => $ambulanceId]);

        $stmt = $this->db->prepare(
            'INSERT INTO ambulance_staff_assignments (
                ambulance_id, user_id, is_active
             ) VALUES (
                :ambulance_id, :user_id, 1
             )'
        );
        $stmt->execute([
            'ambulance_id' => $ambulanceId,
            'user_id' => $userId,
        ]);
    }

    public function updateStatus(int $ambulanceId, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ambulances SET status = :status WHERE ambulance_id = :ambulance_id'
        );
        $stmt->execute([
            'status' => $status,
            'ambulance_id' => $ambulanceId,
        ]);
    }

    public function updateLocation(int $ambulanceId, float $latitude, float $longitude): void
    {
        $stmt = $this->db->prepare(
            'UPDATE ambulances
             SET current_latitude = :latitude, current_longitude = :longitude
             WHERE ambulance_id = :ambulance_id'
        );
        $stmt->execute([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ambulance_id' => $ambulanceId,
        ]);
    }
}

