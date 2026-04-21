<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MedicalProfile extends Model
{
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM medical_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(int $userId, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO medical_profiles (
                user_id, blood_group, allergies, chronic_conditions, notes, emergency_phone
             ) VALUES (
                :user_id, :blood_group, :allergies, :chronic_conditions, :notes, :emergency_phone
             )
             ON DUPLICATE KEY UPDATE
                blood_group = VALUES(blood_group),
                allergies = VALUES(allergies),
                chronic_conditions = VALUES(chronic_conditions),
                notes = VALUES(notes),
                emergency_phone = VALUES(emergency_phone)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'blood_group' => $data['blood_group'] ?: 'Unknown',
            'allergies' => $data['allergies'] ?: null,
            'chronic_conditions' => $data['chronic_conditions'] ?: null,
            'notes' => $data['notes'] ?: null,
            'emergency_phone' => $data['emergency_phone'] ?: null,
        ]);
    }
}

