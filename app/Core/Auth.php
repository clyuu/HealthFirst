<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['auth_user_id'] = (int) $user['user_id'];
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user_id']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['auth_user_id']) ? (int) $_SESSION['auth_user_id'] : null;
    }

    public static function user(): ?array
    {
        $userId = self::id();
        if ($userId === null) {
            return null;
        }

        return (new User())->findDetailedById($userId);
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return $user['role_slug'] === $role;
    }

    public static function hasAnyRole(array $roles): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return in_array($user['role_slug'], $roles, true);
    }
}

