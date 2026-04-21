<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class EmergencyContact extends Model
{
    public function listByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM emergency_contacts
             WHERE user_id = :user_id
             ORDER BY is_primary DESC, contact_name ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO emergency_contacts (
                user_id, contact_name, relationship, phone_number, is_primary
             ) VALUES (
                :user_id, :contact_name, :relationship, :phone_number, :is_primary
             )'
        );
        $stmt->execute([
            'user_id' => $userId,
            'contact_name' => $data['contact_name'],
            'relationship' => $data['relationship'] ?: null,
            'phone_number' => $data['phone_number'],
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
        ]);
    }
}

