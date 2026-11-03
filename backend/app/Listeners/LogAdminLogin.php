<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Models\AdminUser;

class LogAdminLogin
{
    public function handleLogin(Login $event): void
    {
        if (! ($event->user instanceof AdminUser)) return;

        ActivityLogger::logManual(
            logName:    'auth',
            event:      'login',
            action:     ($event->user->name ?? $event->user->email) . ' logged in',
            subject:    $event->user,
            properties: ['guard' => $event->guard],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user || ! ($event->user instanceof AdminUser)) return;

        ActivityLogger::logManual(
            logName:    'auth',
            event:      'logout',
            action:     ($event->user->name ?? $event->user->email) . ' logged out',
            subject:    $event->user,
            properties: ['guard' => $event->guard],
        );
    }
}
