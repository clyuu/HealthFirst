<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class HospitalStaff extends Model
{
    public function create(int $userId, int $hospitalId, string $designation): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hospital_staff (user_id, hospital_id, designation)
             VALUES (:user_id, :hospital_id, :designation)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'designation' => $designation,
        ]);
    }
}

