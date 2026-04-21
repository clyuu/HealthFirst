<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuditLog extends Model
{
    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (
                user_id, action_type, entity_type, entity_id, details, ip_address
             ) VALUES (
                :user_id, :action_type, :entity_type, :entity_id, :details, :ip_address
             )'
        );
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'action_type' => $data['action_type'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'details' => $data['details'] ?? null,
            'ip_address' => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);
    }
}

