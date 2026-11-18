<?php

namespace App\Support;

use App\Role;
use App\Models\User;

class Modules
{
    /** Modul yang boleh dipakai user ini, berdasarkan kode divisinya. */
    public static function forUser(?User $user): array
    {
        if (! $user || $user->role !== Role::Staff) {
            return [];
        }

        $code = $user->division?->code;

        if (! $code) {
            return [];
        }

        return collect(config('modules', []))
            ->filter(fn (array $module) => in_array($code, $module['divisions'], true))
            ->all();
    }

    public static function can(?User $user, string $module): bool
    {
        return array_key_exists($module, self::forUser($user));
    }
}