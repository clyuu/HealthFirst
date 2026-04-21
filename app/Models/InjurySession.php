<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class InjurySession extends Model
{
    public function create(int $incidentId, int $startedByUserId, string $sessionToken, ?string $specialNote = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO injury_sessions (
                incident_id, started_by_user_id, session_token, special_note, status
             ) VALUES (
                :incident_id, :started_by_user_id, :session_token, :special_note, "active"
             )'
        );
        $stmt->execute([
            'incident_id' => $incidentId,
            'started_by_user_id' => $startedByUserId,
            'session_token' => $sessionToken,
            'special_note' => $specialNote,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $sessionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM injury_sessions WHERE session_id = :session_id LIMIT 1');
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetch() ?: null;
    }

    public function latestByIncident(int $incidentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM injury_sessions
             WHERE incident_id = :incident_id
             ORDER BY session_id DESC
             LIMIT 1'
        );
        $stmt->execute(['incident_id' => $incidentId]);
        return $stmt->fetch() ?: null;
    }

    public function addPrediction(int $sessionId, int $incidentId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO injury_image_predictions (
                session_id, incident_id, image_path, predicted_label, confidence_score,
                burns_probability, cuts_probability, normal_probability
             ) VALUES (
                :session_id, :incident_id, :image_path, :predicted_label, :confidence_score,
                :burns_probability, :cuts_probability, :normal_probability
             )'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'incident_id' => $incidentId,
            'image_path' => $data['image_path'],
            'predicted_label' => $data['predicted_label'],
            'confidence_score' => $data['confidence_score'],
            'burns_probability' => $data['burns_probability'],
            'cuts_probability' => $data['cuts_probability'],
            'normal_probability' => $data['normal_probability'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function predictions(int $sessionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM injury_image_predictions
             WHERE session_id = :session_id
             ORDER BY prediction_id ASC'
        );
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function finalize(int $sessionId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE injury_sessions
             SET status = "finalized",
                 finalized_at = CURRENT_TIMESTAMP,
                 overall_severity = :overall_severity,
                 summary_json = :summary_json,
                 report_file_path = :report_file_path,
                 special_note = COALESCE(:special_note, special_note)
             WHERE session_id = :session_id'
        );
        $stmt->execute([
            'overall_severity' => $data['overall_severity'],
            'summary_json' => $data['summary_json'],
            'report_file_path' => $data['report_file_path'],
            'special_note' => $data['special_note'] ?? null,
            'session_id' => $sessionId,
        ]);
    }
}

