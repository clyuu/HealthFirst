<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\Ambulance;
use App\Models\Hospital;
use App\Models\HospitalStaff;
use App\Models\Incident;
use App\Models\User;
use App\Services\ValidationService;
use Throwable;

final class HospitalAdminController extends Controller
{
    public function dashboard(): void
    {
        $user = Auth::user();
        $hospital = (new Hospital())->findByStaffUserId((int) $user['user_id']);

        $this->render('admin/hospital', [
            'title' => 'Hospital Administration',
            'hospital' => $hospital,
            'staff' => $hospital ? (new User())->listHospitalStaff((int) $hospital['hospital_id']) : [],
            'ambulances' => $hospital ? (new Ambulance())->listByHospital((int) $hospital['hospital_id']) : [],
            'incidents' => $hospital ? (new Incident())->listHospitalQueue((int) $hospital['hospital_id']) : [],
        ]);
    }

    public function createStaff(): void
    {
        $this->validateCsrf();

        try {
            $current = Auth::user();
            $hospital = (new Hospital())->findByStaffUserId((int) $current['user_id']);
            $userId = (new User())->createStaff([
                'role_slug' => $_POST['role_slug'],
                'full_name' => trim((string) $_POST['full_name']),
                'nic_number' => ValidationService::assertNic((string) ($_POST['nic_number'] ?? '')),
                'email' => trim((string) $_POST['email']),
                'phone' => ValidationService::assertPhone((string) ($_POST['phone'] ?? '')),
                'password' => ValidationService::assertPassword((string) ($_POST['password'] ?? '')),
                'date_of_birth' => $_POST['date_of_birth'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'address' => trim((string) ($_POST['address'] ?? '')),
            ]);

            (new HospitalStaff())->create($userId, (int) $hospital['hospital_id'], trim((string) $_POST['designation']));

            if ($_POST['role_slug'] === 'paramedic' && !empty($_POST['ambulance_id'])) {
                (new Ambulance())->assignStaff((int) $_POST['ambulance_id'], $userId);
            }

            Flash::success('Hospital staff account created.');
        } catch (Throwable $exception) {
            Flash::error('Unable to create staff member: ' . $exception->getMessage());
        }

        $this->redirect('/admin/hospital');
    }

    public function createAmbulance(): void
    {
        $this->validateCsrf();

        try {
            $current = Auth::user();
            $hospital = (new Hospital())->findByStaffUserId((int) $current['user_id']);
            $ambulanceId = (new Ambulance())->create([
                'hospital_id' => $hospital['hospital_id'],
                'ambulance_number' => trim((string) $_POST['ambulance_number']),
                'status' => $_POST['status'] ?? 'available',
                'capacity_stretchers' => (int) ($_POST['capacity_stretchers'] ?? 1),
            ]);

            if (!empty($_POST['paramedic_user_id'])) {
                (new Ambulance())->assignStaff($ambulanceId, (int) $_POST['paramedic_user_id']);
            }

            Flash::success('Ambulance added successfully.');
        } catch (Throwable $exception) {
            Flash::error('Unable to create ambulance: ' . $exception->getMessage());
        }

        $this->redirect('/admin/hospital');
    }
}
