<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class Hospital extends Model
{
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM hospitals ORDER BY hospital_name ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $hospitalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospitals WHERE hospital_id = :hospital_id LIMIT 1');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetch() ?: null;
    }

    public function findByStaffUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT h.*
             FROM hospital_staff hs
             INNER JOIN hospitals h ON h.hospital_id = hs.hospital_id
             WHERE hs.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hospitals (
                hospital_name, address, latitude, longitude, contact_number
             ) VALUES (
                :hospital_name, :address, :latitude, :longitude, :contact_number
             )'
        );
        $stmt->execute([
            'hospital_name' => $data['hospital_name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'contact_number' => $data['contact_number'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function shortlistByDistance(float $latitude, float $longitude, int $limit = 5): array
    {
        $sql = 'SELECT h.*,
                       (
                           6371 * ACOS(
                               COS(RADIANS(:lat)) *
                               COS(RADIANS(h.latitude)) *
                               COS(RADIANS(h.longitude) - RADIANS(:lng)) +
                               SIN(RADIANS(:lat)) *
                               SIN(RADIANS(h.latitude))
                           )
                       ) AS distance_km,
                       (
                           SELECT COUNT(*)
                           FROM ambulances a
                           WHERE a.hospital_id = h.hospital_id
                             AND a.status = "available"
                       ) AS available_ambulances
                FROM hospitals h
                HAVING available_ambulances > 0
                ORDER BY distance_km ASC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lat', $latitude);
        $stmt->bindValue(':lng', $longitude);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

