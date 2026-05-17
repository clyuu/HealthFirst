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

    public function findByName(string $hospitalName): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospitals WHERE hospital_name = :hospital_name LIMIT 1');
        $stmt->execute(['hospital_name' => $hospitalName]);
        return $stmt->fetch() ?: null;
    }

    public function findByGooglePlaceId(string $placeId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospitals WHERE google_place_id = :google_place_id LIMIT 1');
        $stmt->execute(['google_place_id' => $placeId]);
        return $stmt->fetch() ?: null;
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
                hospital_name, address, latitude, longitude, contact_number,
                ownership, hospital_type, google_place_id, source_url, business_status
             ) VALUES (
                :hospital_name, :address, :latitude, :longitude, :contact_number,
                :ownership, :hospital_type, :google_place_id, :source_url, :business_status
             )'
        );
        $stmt->execute([
            'hospital_name' => $data['hospital_name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'contact_number' => $data['contact_number'],
            'ownership' => $data['ownership'] ?? 'government',
            'hospital_type' => $data['hospital_type'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'business_status' => $data['business_status'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateImported(int $hospitalId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE hospitals
             SET hospital_name = :hospital_name,
                 address = :address,
                 latitude = :latitude,
                 longitude = :longitude,
                 contact_number = :contact_number,
                 ownership = :ownership,
                 hospital_type = :hospital_type,
                 google_place_id = :google_place_id,
                 source_url = :source_url,
                 business_status = :business_status
             WHERE hospital_id = :hospital_id'
        );
        $stmt->execute([
            'hospital_id' => $hospitalId,
            'hospital_name' => $data['hospital_name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'contact_number' => $data['contact_number'],
            'ownership' => $data['ownership'] ?? 'government',
            'hospital_type' => $data['hospital_type'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'business_status' => $data['business_status'] ?? null,
        ]);
    }

    public function shortlistByDistance(float $latitude, float $longitude, int $limit = 5, bool $requireAvailableAmbulances = false): array
    {
        $havingClause = $requireAvailableAmbulances ? 'HAVING available_ambulances > 0' : '';

        $sql = 'SELECT h.*,
                       (
                           6371 * ACOS(
                               COS(RADIANS(:origin_lat_cos)) *
                               COS(RADIANS(h.latitude)) *
                               COS(RADIANS(h.longitude) - RADIANS(:origin_lng)) +
                               SIN(RADIANS(:origin_lat_sin)) *
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
                ' . $havingClause . '
                ORDER BY distance_km ASC
                LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':origin_lat_cos', $latitude);
        $stmt->bindValue(':origin_lat_sin', $latitude);
        $stmt->bindValue(':origin_lng', $longitude);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
