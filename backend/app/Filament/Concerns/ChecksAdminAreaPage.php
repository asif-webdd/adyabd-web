<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/** Same as ChecksAdminArea, for custom Filament pages (Settings, Appearance, Reports). */
trait ChecksAdminAreaPage
{
    public static function canAccess(): bool
    {
        $admin = Auth::guard('admin')->user();
        return $admin && $admin->canArea(static::$area);
    }
}
