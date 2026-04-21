<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.role_name
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findByNic(string $nic): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.role_name
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE u.nic_number = :nic
             LIMIT 1'
        );
        $stmt->execute(['nic' => $nic]);
        return $stmt->fetch() ?: null;
    }

    public function findDetailedById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.slug AS role_slug, r.role_name,
                    hs.hospital_id, hs.designation, h.hospital_name
             FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             LEFT JOIN hospital_staff hs ON hs.user_id = u.user_id
             LEFT JOIN hospitals h ON h.hospital_id = hs.hospital_id
             WHERE u.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function updateSelf(int $userId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 phone = :phone,
                 address = :address,
                 date_of_birth = :date_of_birth,
                 profile_latitude = :profile_latitude,
                 profile_longitude = :profile_longitude
             WHERE user_id = :user_id'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'date_of_birth' => $data['date_of_birth'] ?: null,
            'profile_latitude' => $data['profile_latitude'] ?: null,
            'profile_longitude' => $data['profile_longitude'] ?: null,
            'user_id' => $userId,
        ]);
    }

    public function createPatient(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (
                role_id, full_name, nic_number, email, phone, address,
                password_hash, date_of_birth, gender, profile_latitude, profile_longitude
             ) VALUES (
                :role_id, :full_name, :nic_number, :email, :phone, :address,
                :password_hash, :date_of_birth, :gender, :profile_latitude, :profile_longitude
             )'
        );
        $stmt->execute([
            'role_id' => $this->roleIdBySlug('patient'),
            'full_name' => $data['full_name'],
            'nic_number' => $data['nic_number'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'password_hash' => $data['password_hash'],
            'date_of_birth' => $data['date_of_birth'] ?: null,
            'gender' => $data['gender'] ?: null,
            'profile_latitude' => $data['profile_latitude'] ?: null,
            'profile_longitude' => $data['profile_longitude'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createStaff(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (
                role_id, full_name, nic_number, email, phone, address,
                password_hash, date_of_birth, gender
             ) VALUES (
                :role_id, :full_name, :nic_number, :email, :phone, :address,
                :password_hash, :date_of_birth, :gender
             )'
        );
        $stmt->execute([
            'role_id' => $this->roleIdBySlug($data['role_slug']),
            'full_name' => $data['full_name'],
            'nic_number' => $data['nic_number'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'date_of_birth' => $data['date_of_birth'] ?: null,
            'gender' => $data['gender'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function listHospitalStaff(int $hospitalId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.user_id, u.full_name, u.email, u.phone, r.role_name, r.slug AS role_slug, hs.designation
             FROM hospital_staff hs
             INNER JOIN users u ON u.user_id = hs.user_id
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE hs.hospital_id = :hospital_id
             ORDER BY FIELD(r.slug, "hospital_admin", "doctor", "paramedic"), u.full_name'
        );
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function countByRole(string $roleSlug): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE r.slug = :slug'
        );
        $stmt->execute(['slug' => $roleSlug]);
        return (int) $stmt->fetchColumn();
    }

    public function roleIdBySlug(string $slug): int
    {
        $stmt = $this->db->prepare('SELECT role_id FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return (int) $stmt->fetchColumn();
    }
}
