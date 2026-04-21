<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dispatch;
use App\Models\DoctorCaseAssignment;
use App\Models\Incident;
use App\Models\MedicalDocument;
use RuntimeException;

final class DoctorWorkflowService
{
    public function admit(int $incidentId, int $doctorUserId, int $hospitalId): int
    {
        $caseId = (new DoctorCaseAssignment())->admit($incidentId, $doctorUserId, $hospitalId);
        (new Incident())->updateStatus($incidentId, 'admitted', $doctorUserId, 'Doctor admitted the patient into hospital care.');
        (new Dispatch())->markAdmitted($incidentId);
        return $caseId;
    }

    public function uploadCaseDocument(int $caseId, int $uploadedByUserId, array $file, string $title, ?string $description = null): void
    {
        $case = (new DoctorCaseAssignment())->findById($caseId);
        if ($case === null) {
            throw new RuntimeException('Doctor case not found.');
        }

        $stored = (new DocumentService())->storeUploadedFile($file, 'documents');
        (new MedicalDocument())->create([
            'user_id' => $case['user_id'],
            'incident_id' => $case['incident_id'],
            'hospital_id' => $case['hospital_id'],
            'uploaded_by_user_id' => $uploadedByUserId,
            'document_category' => 'discharge_or_treatment',
            'source_type' => 'doctor_upload',
            'title' => $title,
            'description' => $description,
            'file_path' => $stored['file_path'],
            'mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
        ]);
    }

    public function discharge(int $caseId, int $doctorUserId): void
    {
        $case = (new DoctorCaseAssignment())->findById($caseId);
        if ($case === null) {
            throw new RuntimeException('Doctor case not found.');
        }

        (new DoctorCaseAssignment())->discharge($caseId);
        (new Incident())->updateStatus((int) $case['incident_id'], 'discharged', $doctorUserId, 'Patient discharged by assigned doctor.');
        (new Dispatch())->markDischarged((int) $case['incident_id']);
    }
}

