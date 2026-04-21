<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Hospital;
use App\Models\Incident;
use App\Models\MedicalDocument;
use App\Services\DoctorWorkflowService;
use Throwable;

final class DoctorController extends Controller
{
    public function dashboard(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);

        $this->render('doctor/dashboard', [
            'title' => 'Doctor Dashboard',
            'hospital' => $hospital,
            'incidents' => $hospital ? (new Incident())->listDoctorFeed((int) $hospital['hospital_id']) : [],
        ]);
    }

    public function admit(int $incidentId): void
    {
        $this->validateCsrf();

        try {
            $user = Auth::user();
            $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);
            $caseId = (new DoctorWorkflowService())->admit($incidentId, (int) $user['user_id'], (int) $hospital['hospital_id']);
            Flash::success('Patient admitted and assigned to you.');
            $this->json(['case_id' => $caseId]);
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function myPatients(): void
    {
        $patients = (new Incident())->listMyPatients((int) Auth::id());
        $documents = [];
        foreach ($patients as $patient) {
            $documents[(int) $patient['user_id']] = (new MedicalDocument())->listByUserId((int) $patient['user_id']);
        }

        $this->render('doctor/patients', [
            'title' => 'My Patients',
            'patients' => $patients,
            'documents' => $documents,
        ]);
    }

    public function uploadDocument(int $caseId): void
    {
        $this->validateCsrf();

        try {
            (new DoctorWorkflowService())->uploadCaseDocument(
                $caseId,
                (int) Auth::id(),
                $_FILES['document'],
                trim((string) $_POST['title']),
                trim((string) ($_POST['description'] ?? ''))
            );
            Flash::success('Doctor document added to patient history.');
        } catch (Throwable $exception) {
            Flash::error('Unable to upload doctor document: ' . $exception->getMessage());
        }

        $this->redirect('/doctor/patients');
    }

    public function discharge(int $caseId): void
    {
        $this->validateCsrf();

        try {
            (new DoctorWorkflowService())->discharge($caseId, (int) Auth::id());
            Flash::success('Patient discharged successfully.');
        } catch (Throwable $exception) {
            Flash::error('Unable to discharge patient: ' . $exception->getMessage());
        }

        $this->redirect('/doctor/patients');
    }
}

