<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Ambulance;
use App\Models\Hospital;
use App\Models\HospitalStaff;
use App\Models\User;

require __DIR__ . '/../app/bootstrap.php';

$hospitalModel = new Hospital();
$ambulanceModel = new Ambulance();
$userModel = new User();
$hospitalStaffModel = new HospitalStaff();
$db = Database::connection();
$created = 0;
$minimumPerHospital = 2;

foreach ($hospitalModel->all() as $hospital) {
    $hospitalId = (int) $hospital['hospital_id'];
    $existing = $ambulanceModel->listByHospital($hospitalId);
    $existingCount = count($existing);
    for ($index = $existingCount + 1; $index <= $minimumPerHospital; $index++) {
        $ambulanceModel->create([
            'hospital_id' => $hospitalId,
            'ambulance_number' => $index === 1
                ? sprintf('HF-AMB-%04d', $hospitalId)
                : sprintf('HF-AMB-%04d-%d', $hospitalId, $index),
            'status' => 'available',
            'capacity_stretchers' => 1,
            'current_latitude' => $hospital['latitude'] ?? null,
            'current_longitude' => $hospital['longitude'] ?? null,
        ]);
        $created++;
    }
}

foreach ($db->query('
    SELECT a.ambulance_id, a.hospital_id, a.ambulance_number, h.hospital_name
    FROM ambulances a
    INNER JOIN hospitals h ON h.hospital_id = a.hospital_id
    LEFT JOIN ambulance_staff_assignments asa
      ON asa.ambulance_id = a.ambulance_id AND asa.is_active = 1
    WHERE asa.assignment_id IS NULL
')->fetchAll() as $ambulance) {
    $ambulanceId = (int) $ambulance['ambulance_id'];
    $hospitalId = (int) $ambulance['hospital_id'];
    $email = sprintf('ambulance%d.paramedic@healthfirst.lk', $ambulanceId);
    $user = $userModel->findByEmail($email);
    if ($user === null) {
        $userId = $userModel->createStaff([
            'role_slug' => 'paramedic',
            'full_name' => $ambulance['ambulance_number'] . ' Paramedic',
            'nic_number' => sprintf('PAR%06d', $ambulanceId),
            'email' => $email,
            'phone' => 'N/A',
            'password' => 'Password@123',
            'date_of_birth' => null,
            'gender' => null,
            'address' => $ambulance['hospital_name'],
        ]);
    } else {
        $userId = (int) $user['user_id'];
    }

    $stmt = $db->prepare('SELECT 1 FROM hospital_staff WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    if (!$stmt->fetchColumn()) {
        $hospitalStaffModel->create($userId, $hospitalId, 'Ambulance Crew');
    }

    $ambulanceModel->assignStaff($ambulanceId, $userId);
}

echo 'Default ambulances created: ' . $created . PHP_EOL;
