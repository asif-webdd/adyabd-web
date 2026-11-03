<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseNotifications extends Command
{
    protected $signature   = 'admin:diagnose-notifications';
    protected $description = 'Diagnose why admin notifications are empty';

    public function handle(): int
    {
        $this->info('=== Admin Notification Diagnosis ===');
        $this->newLine();

        // 1. Check active admins
        $admins = AdminUser::where('is_active', true)->get(['id', 'name', 'email']);
        $this->info("Active admins: {$admins->count()}");
        foreach ($admins as $a) {
            $this->line("  → [{$a->id}] {$a->name} ({$a->email})");
        }
        $this->newLine();

        // 2. Check notifications table rows for AdminUser
        $total = DB::table('notifications')->where('notifiable_type', AdminUser::class)->count();
        $this->info("Rows in notifications table for AdminUser: {$total}");

        // 3. Show all distinct notifiable_types stored
        $types = DB::table('notifications')->distinct()->pluck('notifiable_type');
        $this->info('All notifiable_type values in notifications table:');
        foreach ($types as $t) {
            $this->line("  → {$t}");
        }
        $this->newLine();

        if ($total === 0) {
            $this->warn('  No notifications found for AdminUser.');
            $this->warn('   Run: php artisan admin:seed-notifications --fresh');
        } else {
            $this->info(" Notifications exist. If panel still shows empty, check the Filament auth guard.");
        }

        return self::SUCCESS;
    }
}
