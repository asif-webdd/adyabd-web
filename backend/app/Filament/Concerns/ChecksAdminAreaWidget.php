<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

trait ChecksAdminAreaWidget
{
    public static function canView(): bool
    {
        $admin = Auth::guard('admin')->user();
        return $admin && $admin->canArea(static::$area);
    }
}
