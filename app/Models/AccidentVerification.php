<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AccidentVerification extends Model
{
    public function upsert(int $incidentId, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO accident_verifications (
                incident_id, result, confidence_score, raw_prediction, model_version
             ) VALUES (
                :incident_id, :result, :confidence_score, :raw_prediction, :model_version
             )
             ON DUPLICATE KEY UPDATE
                result = VALUES(result),
                confidence_score = VALUES(confidence_score),
                raw_prediction = VALUES(raw_prediction),
                model_version = VALUES(model_version),
                verified_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'result' => $data['result'],
            'confidence_score' => $data['confidence_score'],
            'raw_prediction' => $data['raw_prediction'],
            'model_version' => $data['model_version'] ?? 'accident-v1',
        ]);
    }
}

