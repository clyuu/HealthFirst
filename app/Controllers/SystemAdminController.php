<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Hospital;
use App\Models\HospitalStaff;
use App\Models\User;
use Throwable;

final class SystemAdminController extends Controller
{
    public function dashboard(): void
    {
        $this->render('admin/system', [
            'title' => 'System Administration',
            'hospitals' => (new Hospital())->all(),
            'patientCount' => (new User())->countByRole('patient'),
            'doctorCount' => (new User())->countByRole('doctor'),
            'paramedicCount' => (new User())->countByRole('paramedic'),
        ]);
    }

    public function createHospital(): void
    {
        $this->validateCsrf();

        try {
            $hospitalId = (new Hospital())->create([
                'hospital_name' => trim((string) $_POST['hospital_name']),
                'address' => trim((string) $_POST['address']),
                'latitude' => (float) $_POST['latitude'],
                'longitude' => (float) $_POST['longitude'],
                'contact_number' => trim((string) $_POST['contact_number']),
            ]);

            if (!empty($_POST['admin_email'])) {
                $adminId = (new User())->createStaff([
                    'role_slug' => 'hospital_admin',
                    'full_name' => trim((string) $_POST['admin_name']),
                    'nic_number' => trim((string) $_POST['admin_nic']),
                    'email' => trim((string) $_POST['admin_email']),
                    'phone' => trim((string) $_POST['admin_phone']),
                    'password' => (string) $_POST['admin_password'],
                    'date_of_birth' => $_POST['admin_date_of_birth'] ?? null,
                    'gender' => $_POST['admin_gender'] ?? null,
                    'address' => trim((string) ($_POST['admin_address'] ?? '')),
                ]);
                (new HospitalStaff())->create($adminId, $hospitalId, 'Primary Hospital Administrator');
            }

            Flash::success('Hospital created successfully.');
        } catch (Throwable $exception) {
            Flash::error('Unable to create hospital: ' . $exception->getMessage());
        }

        $this->redirect('/admin/system');
    }
}

