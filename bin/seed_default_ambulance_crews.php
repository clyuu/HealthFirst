<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Ambulance;
use App\Models\HospitalStaff;
use App\Models\User;

require __DIR__ . '/../app/bootstrap.php';

$db = Database::connection();
$ambulanceModel = new Ambulance();
$userModel = new User();
$hospitalStaffModel = new HospitalStaff();
$created = 0;
$reportRows = [];

$ambulances = $db->query('
    SELECT a.ambulance_id, a.hospital_id, a.ambulance_number, h.hospital_name
    FROM ambulances a
    INNER JOIN hospitals h ON h.hospital_id = a.hospital_id
    LEFT JOIN ambulance_staff_assignments asa
      ON asa.ambulance_id = a.ambulance_id AND asa.is_active = 1
    WHERE asa.assignment_id IS NULL
    ORDER BY a.hospital_id ASC, a.ambulance_id ASC
')->fetchAll();

foreach ($ambulances as $ambulance) {
    $ambulanceId = (int) $ambulance['ambulance_id'];
    $hospitalId = (int) $ambulance['hospital_id'];
    $ambulanceNumber = (string) $ambulance['ambulance_number'];
    $hospitalName = (string) $ambulance['hospital_name'];
    $email = sprintf('ambulance%d.paramedic@healthfirst.lk', $ambulanceId);

    $user = $userModel->findByEmail($email);
    if ($user === null) {
        $userId = $userModel->createStaff([
            'role_slug' => 'paramedic',
            'full_name' => $ambulanceNumber . ' Paramedic',
            'nic_number' => sprintf('PAR%06d', $ambulanceId),
            'email' => $email,
            'phone' => 'N/A',
            'password' => 'Password@123',
            'date_of_birth' => null,
            'gender' => null,
            'address' => $hospitalName,
        ]);
        $created++;
    } else {
        $userId = (int) $user['user_id'];
    }

    $stmt = $db->prepare('SELECT 1 FROM hospital_staff WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    if (!$stmt->fetchColumn()) {
        $hospitalStaffModel->create($userId, $hospitalId, 'Ambulance Crew');
    }

    $ambulanceModel->assignStaff($ambulanceId, $userId);

    $reportRows[] = [
        'ambulance_id' => $ambulanceId,
        'ambulance_number' => $ambulanceNumber,
        'hospital_id' => $hospitalId,
        'hospital_name' => $hospitalName,
        'email' => $email,
        'password' => 'Password@123',
    ];
}

$reportPath = BASE_PATH . '/storage/generated/imports/default-ambulance-crews.csv';
$handle = fopen($reportPath, 'wb');
if ($handle !== false) {
    fputcsv($handle, ['ambulance_id', 'ambulance_number', 'hospital_id', 'hospital_name', 'email', 'password']);
    foreach ($reportRows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
}

echo 'Default ambulance crews created: ' . $created . PHP_EOL;
echo 'Crew report: ' . $reportPath . PHP_EOL;
