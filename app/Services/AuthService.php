<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\EmergencyContact;
use App\Models\MedicalProfile;
use App\Models\User;
use InvalidArgumentException;

final class AuthService
{
    public function login(string $email, string $password): bool
    {
        $user = (new User())->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        Auth::login($user);
        return true;
    }

    public function registerPatient(array $data): array
    {
        $required = ['full_name', 'nic_number', 'email', 'phone', 'address', 'password'];
        foreach ($required as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Please complete all required registration fields.');
            }
        }

        $userModel = new User();
        if ($userModel->findByEmail($data['email'])) {
            throw new InvalidArgumentException('An account already exists for that email address.');
        }
        if ($userModel->findByNic($data['nic_number'])) {
            throw new InvalidArgumentException('An account already exists for that NIC number.');
        }

        $userId = $userModel->createPatient([
            ...$data,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        (new MedicalProfile())->upsert($userId, [
            'blood_group' => $data['blood_group'] ?? 'Unknown',
            'allergies' => $data['allergies'] ?? null,
            'chronic_conditions' => $data['chronic_conditions'] ?? null,
            'notes' => $data['notes'] ?? null,
            'emergency_phone' => $data['emergency_phone'] ?? null,
        ]);

        if (!empty($data['contact_name']) && !empty($data['contact_phone'])) {
            (new EmergencyContact())->create($userId, [
                'contact_name' => $data['contact_name'],
                'relationship' => $data['contact_relationship'] ?? '',
                'phone_number' => $data['contact_phone'],
                'is_primary' => 1,
            ]);
        }

        $qr = (new QrCodeService())->ensureForUser($userId);
        $user = $userModel->findDetailedById($userId);
        Auth::login($user ?? ['user_id' => $userId]);

        return ['user_id' => $userId, 'qr' => $qr];
    }
}

