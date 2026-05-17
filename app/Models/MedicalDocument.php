<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MedicalDocument extends Model
{
    public function listByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT md.*, u.full_name AS uploaded_by_name, h.hospital_name
             FROM medical_documents md
             LEFT JOIN users u ON u.user_id = md.uploaded_by_user_id
             LEFT JOIN hospitals h ON h.hospital_id = md.hospital_id
             WHERE md.user_id = :user_id
             ORDER BY md.uploaded_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function listPatientUploadsByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT md.*, u.full_name AS uploaded_by_name, h.hospital_name
             FROM medical_documents md
             LEFT JOIN users u ON u.user_id = md.uploaded_by_user_id
             LEFT JOIN hospitals h ON h.hospital_id = md.hospital_id
             WHERE md.user_id = :user_id
               AND md.source_type = :source_type
             ORDER BY md.uploaded_at DESC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'source_type' => 'patient_upload',
        ]);
        return $stmt->fetchAll();
    }

    public function listVisibleToPatientByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT md.*, u.full_name AS uploaded_by_name, h.hospital_name
             FROM medical_documents md
             LEFT JOIN users u ON u.user_id = md.uploaded_by_user_id
             LEFT JOIN hospitals h ON h.hospital_id = md.hospital_id
             WHERE md.user_id = :user_id
               AND md.source_type IN ("patient_upload", "hospital_upload")
             ORDER BY md.uploaded_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function listByIncident(int $incidentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM medical_documents
             WHERE incident_id = :incident_id
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute(['incident_id' => $incidentId]);
        return $stmt->fetchAll();
    }

    public function findById(int $documentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM medical_documents WHERE document_id = :document_id LIMIT 1');
        $stmt->execute(['document_id' => $documentId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO medical_documents (
                user_id, incident_id, hospital_id, uploaded_by_user_id,
                document_category, source_type, title, description,
                file_path, mime_type, file_size
             ) VALUES (
                :user_id, :incident_id, :hospital_id, :uploaded_by_user_id,
                :document_category, :source_type, :title, :description,
                :file_path, :mime_type, :file_size
             )'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'incident_id' => $data['incident_id'] ?? null,
            'hospital_id' => $data['hospital_id'] ?? null,
            'uploaded_by_user_id' => $data['uploaded_by_user_id'] ?? null,
            'document_category' => $data['document_category'],
            'source_type' => $data['source_type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}
