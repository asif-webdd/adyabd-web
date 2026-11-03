<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/** Only admins with full access (no role, or a "super" role) may manage admin users and roles. */
trait SuperAdminOnly
{
    protected static function superOnly(): bool
    {
        $admin = Auth::guard('admin')->user();
        return $admin && $admin->isSuper();
    }

    public static function canViewAny(): bool { return static::superOnly(); }
    public static function canCreate(): bool { return static::superOnly(); }
    public static function canEdit($record): bool { return static::superOnly(); }
    public static function canView($record): bool { return static::superOnly(); }
    public static function canDeleteAny(): bool { return static::superOnly(); }
}
