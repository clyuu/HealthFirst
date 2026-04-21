<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PatientVital extends Model
{
    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO patient_vitals (
                incident_id, recorded_by_user_id, heart_rate, systolic_bp, diastolic_bp,
                spo2, temperature_c, notes
             ) VALUES (
                :incident_id, :recorded_by_user_id, :heart_rate, :systolic_bp, :diastolic_bp,
                :spo2, :temperature_c, :notes
             )'
        );
        $stmt->execute([
            'incident_id' => $data['incident_id'],
            'recorded_by_user_id' => $data['recorded_by_user_id'],
            'heart_rate' => $data['heart_rate'] ?: null,
            'systolic_bp' => $data['systolic_bp'] ?: null,
            'diastolic_bp' => $data['diastolic_bp'] ?: null,
            'spo2' => $data['spo2'] ?: null,
            'temperature_c' => $data['temperature_c'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);
    }
}

