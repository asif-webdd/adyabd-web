<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Role based access for a Filament Resource. The resource declares `protected static string $area = '...'`
 * (see App\Support\AdminAreas); admins whose role lacks that area cannot see or open it (also not by typing the URL).
 */
trait ChecksAdminArea
{
    protected static function areaAllowed(): bool
    {
        $admin = Auth::guard('admin')->user();
        return $admin && $admin->canArea(static::$area);
    }

    public static function canViewAny(): bool { return static::areaAllowed(); }
    public static function canCreate(): bool { return static::areaAllowed(); }
    public static function canEdit($record): bool { return static::areaAllowed(); }
    public static function canView($record): bool { return static::areaAllowed(); }
    public static function canDelete($record): bool { return static::areaAllowed(); }
    public static function canDeleteAny(): bool { return static::areaAllowed(); }
}
