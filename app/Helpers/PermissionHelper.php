<?php

namespace App\Helpers;

class PermissionHelper
{
    public const MODULE_KEYS = [
        'dashboard', 'messages', 'products', 'inventory', 'procurement',
        'sales', 'manufacturing', 'accounting', 'customers', 'suppliers',
        'reports', 'users', 'employees', 'attendance', 'settings',
    ];

    public static function getDefaultPermissions(string $role): array
    {
        $all = fn(string $level) => array_combine(
            self::MODULE_KEYS,
            array_fill(0, count(self::MODULE_KEYS), $level)
        );

        return match ($role) {
            'admin' => $all('admin'),
            'manager' => array_merge($all('manager'), ['users' => '', 'settings' => '']),
            'staff' => array_merge($all(''), ['dashboard' => 'staff', 'attendance' => 'staff']),
            default => $all(''),
        };
    }

    public static function canAccessModule(?array $permissions, string $key): bool
    {
        if (!$permissions) return false;
        return isset($permissions[$key]) && $permissions[$key] !== '';
    }

    public static function parsePermissions(?string $raw): ?array
    {
        if (!$raw) return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
