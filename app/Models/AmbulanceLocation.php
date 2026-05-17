<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AmbulanceLocation extends Model
{
    public function latestForAmbulanceIncident(int $ambulanceId, int $incidentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM ambulance_locations
             WHERE ambulance_id = :ambulance_id
               AND incident_id = :incident_id
             ORDER BY recorded_at DESC
             LIMIT 1'
        );
        $stmt->execute([
            'ambulance_id' => $ambulanceId,
            'incident_id' => $incidentId,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function store(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ambulance_locations (
                ambulance_id, incident_id, latitude, longitude, speed_kmh, source
             ) VALUES (
                :ambulance_id, :incident_id, :latitude, :longitude, :speed_kmh, :source
             )'
        );
        $stmt->execute([
            'ambulance_id' => $data['ambulance_id'],
            'incident_id' => $data['incident_id'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed_kmh' => $data['speed_kmh'] ?? null,
            'source' => $data['source'] ?? 'gps',
        ]);
    }
}
