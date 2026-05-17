<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Hospital;
use App\Models\HospitalStaff;
use App\Models\User;
use App\Services\ValidationService;
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
            $hospitalName = trim((string) ($_POST['hospital_name'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
            if ($hospitalName === '' || $address === '' || $contactNumber === '') {
                throw new \RuntimeException('Hospital name, address, and contact number are required.');
            }

            (new Hospital())->create([
                'hospital_name' => $hospitalName,
                'address' => $address,
                'latitude' => (float) ($_POST['latitude'] ?? 0),
                'longitude' => (float) ($_POST['longitude'] ?? 0),
                'contact_number' => $contactNumber,
            ]);

            Flash::success('Hospital created successfully.');
        } catch (Throwable $exception) {
            Flash::error('Unable to create hospital: ' . $exception->getMessage());
        }

        $this->redirect('/admin/system');
    }

    public function createHospitalAdmin(): void
    {
        $this->validateCsrf();

        try {
            $hospitalId = (int) ($_POST['hospital_id'] ?? 0);
            $hospital = (new Hospital())->findById($hospitalId);
            if ($hospital === null) {
                throw new \RuntimeException('Please select a valid hospital.');
            }

            $password = ValidationService::assertPassword(trim((string) ($_POST['password'] ?? '')));
            $email = trim((string) ($_POST['email'] ?? ''));
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $nicNumber = ValidationService::assertNic((string) ($_POST['nic_number'] ?? ''));
            $phone = ValidationService::assertPhone((string) ($_POST['phone'] ?? ''));
            if ($fullName === '' || $nicNumber === '' || $email === '' || $phone === '' || $password === '') {
                throw new \RuntimeException('Full name, NIC, email, phone, and password are required.');
            }

            $users = new User();
            if ($users->findByEmail($email) !== null) {
                throw new \RuntimeException('A user with this email already exists.');
            }
            if ($users->findByNic($nicNumber) !== null) {
                throw new \RuntimeException('A user with this NIC already exists.');
            }

            $adminId = $users->createStaff([
                'role_slug' => 'hospital_admin',
                'full_name' => $fullName,
                'nic_number' => $nicNumber,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'date_of_birth' => $_POST['date_of_birth'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'address' => trim((string) ($_POST['address'] ?? $hospital['address'] ?? '')),
            ]);

            (new HospitalStaff())->create(
                $adminId,
                $hospitalId,
                trim((string) ($_POST['designation'] ?? 'Primary Hospital Administrator'))
            );

            Flash::success('Hospital admin created for ' . $hospital['hospital_name'] . '. Login email: ' . $email . ' | Password: ' . $password);
        } catch (Throwable $exception) {
            Flash::error('Unable to create hospital admin: ' . $exception->getMessage());
        }

        $this->redirect('/admin/system');
    }
}
