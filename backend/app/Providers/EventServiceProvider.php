<?php

namespace App\Providers;

use App\Listeners\LogAdminLogin;
use Illuminate\Auth\Events\{Login, Logout};
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class  => [LogAdminLogin::class . '@handleLogin'],
        Logout::class => [LogAdminLogin::class . '@handleLogout'],
    ];

    public function boot(): void {}
    public function shouldDiscoverEvents(): bool { return false; }
}
