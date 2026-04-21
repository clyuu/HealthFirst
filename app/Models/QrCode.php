<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class QrCode extends Model
{
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM qr_codes
             WHERE user_id = :user_id AND status = "active"
             ORDER BY qr_id DESC
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT q.*, u.full_name, u.nic_number, u.phone, u.address, u.date_of_birth,
                    mp.blood_group, mp.allergies, mp.chronic_conditions, mp.notes, mp.emergency_phone
             FROM qr_codes q
             INNER JOIN users u ON u.user_id = q.user_id
             LEFT JOIN medical_profiles mp ON mp.user_id = u.user_id
             WHERE q.public_token = :token AND q.status = "active"
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, string $publicToken, string $qrValue, string $imagePath): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO qr_codes (
                user_id, public_token, qr_value, image_path, status
             ) VALUES (
                :user_id, :public_token, :qr_value, :image_path, "active"
             )'
        );
        $stmt->execute([
            'user_id' => $userId,
            'public_token' => $publicToken,
            'qr_value' => $qrValue,
            'image_path' => $imagePath,
        ]);

        return (int) $this->db->lastInsertId();
    }
}

