<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class ValidationService
{
    public const BLOOD_GROUPS = ['Unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public static function normalizeNic(string $nic): string
    {
        return strtoupper(trim($nic));
    }

    public static function assertNic(string $nic): string
    {
        $normalized = self::normalizeNic($nic);
        if (!preg_match('/^(?:\d{9}[VX]|\d{12})$/', $normalized)) {
            throw new InvalidArgumentException('NIC must be 9 digits followed by V/X, or exactly 12 digits.');
        }

        return $normalized;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s-]+/', '', trim($phone)) ?? '';
    }

    public static function assertPhone(string $phone, string $label = 'Phone'): string
    {
        $normalized = self::normalizePhone($phone);
        if (!preg_match('/^0\d{9}$/', $normalized)) {
            throw new InvalidArgumentException($label . ' must be exactly 10 digits, such as 0771234567.');
        }

        return $normalized;
    }

    public static function assertOptionalPhone(?string $phone, string $label = 'Phone'): ?string
    {
        $value = trim((string) $phone);
        if ($value === '') {
            return null;
        }

        return self::assertPhone($value, $label);
    }

    public static function assertPassword(string $password): string
    {
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
            throw new InvalidArgumentException('Password must be at least 8 characters and include both letters and numbers.');
        }

        return $password;
    }

    public static function assertBloodGroup(string $bloodGroup): string
    {
        $value = trim($bloodGroup) ?: 'Unknown';
        if (!in_array($value, self::BLOOD_GROUPS, true)) {
            throw new InvalidArgumentException('Please select a valid blood group.');
        }

        return $value;
    }

    public static function assertLocation(?string $latitude, ?string $longitude): array
    {
        $lat = trim((string) $latitude);
        $lng = trim((string) $longitude);
        if ($lat === '' && $lng === '') {
            return ['latitude' => null, 'longitude' => null];
        }

        if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            throw new InvalidArgumentException('Please select a valid home location from the map.');
        }

        $latitudeValue = (float) $lat;
        $longitudeValue = (float) $lng;
        if ($latitudeValue < -90 || $latitudeValue > 90 || $longitudeValue < -180 || $longitudeValue > 180) {
            throw new InvalidArgumentException('Selected location coordinates are out of range.');
        }

        return ['latitude' => $latitudeValue, 'longitude' => $longitudeValue];
    }
}
