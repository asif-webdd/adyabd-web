<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{Artisan, Schedule};
use App\Models\{Order, IpBlock, User};

// ─── Artisan Commands ───────────────────────────────────────

Artisan::command('bazar:process-auto-blocks', function () {
    // Check expired IP blocks and deactivate them
    $expired = IpBlock::where('is_active', true)->whereNotNull('expires_at')->where('expires_at', '<', now())->get();
    foreach ($expired as $block) {
        $block->update(['is_active' => false]);
        $this->info("IP {$block->ip_address} block expired and deactivated.");
    }
    $this->info("Processed {$expired->count()} expired IP blocks.");
})->purpose('Process expired IP blocks and auto-block rules');

Artisan::command('bazar:update-courier-status', function () {
    // Poll the courier for parcels that are booked but not yet delivered/cancelled
    // (webhooks are the primary channel; this catches missed ones).
    $orders = Order::whereNotNull('courier_tracking_id')
        ->whereIn('courier_status', ['booked', 'pending', 'in_transit'])
        ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
        ->with('courierMerchant')
        ->limit(50)->get();

    $this->info("Checking {$orders->count()} in-transit orders...");
    $courier = app(\App\Services\CourierService::class);
    $updated = 0;
    foreach ($orders as $order) {
        if (!$order->courierMerchant) continue;
        $status = $courier->fetchStatus($order->courierMerchant, $order);
        if ($status) {
            $order->applyCourierStatus($status, 'poll', ['delivery_status' => $status]);
            $updated++;
            $this->line("  #{$order->order_number}: {$status}");
        }
    }
    $this->info("Updated {$updated} orders.");
})->purpose('Update courier delivery status from APIs');

Artisan::command('bazar:daily-report', function () {
    $date     = today()->format('d M Y');
    $orders   = Order::today()->count();
    $revenue  = Order::today()->where('status', 'delivered')->sum('total_amount');
    $newUsers = User::whereDate('created_at', today())->count();

    $this->info("📊 Daily Report - {$date}");
    $this->info("Orders: {$orders} | Revenue: ৳{$revenue} | New Users: {$newUsers}");

    // Send daily report SMS to admin
    $adminPhone = setting('admin_report_phone');
    if ($adminPhone) {
        app(\App\Services\SmsService::class)->send(
            $adminPhone,
            setting('shop_name_bn') . " দৈনিক রিপোর্ট ({$date}): অর্ডার {$orders}টি, আয় ৳" . number_format($revenue, 0) . ", নতুন গ্রাহক {$newUsers}জন।"
        );
    }
})->purpose('Send daily summary report');

Artisan::command('bazar:cleanup-carts', function () {
    $deleted = \App\Models\Cart::where('created_at', '<', now()->subDays(30))->delete();
    $this->info("Cleaned {$deleted} old cart items.");
})->purpose('Remove old abandoned cart items');

Artisan::command('shop:setup', function () {
    // Safe to run any number of times: creates/updates tables; seeds demo data ONLY into an empty database.
    $this->call('migrate', ['--force' => true]);
    if (!User::query()->exists()) {
        $this->info('Empty database - loading demo data (this takes a minute)...');
        $this->call('db:seed', ['--force' => true]);
    } else {
        $this->info('Existing data found - kept as it is (only new migrations were applied).');
    }
})->purpose('Create tables (and demo data on a fresh database) - used by setup.sh / setup.ps1');






// backend/routes/console.php te 'Scheduled Tasks' comment er THIK AGE ei block ta boshan

Artisan::command('shop:prod-setup', function () {
    // PRODUCTION-SAFE: runs after every deploy. Applies new migrations and creates the bare essentials ONCE
    // (admin account, Passport client, default settings/templates). No demo products/users/orders, and it never
    // overwrites settings the admin has already edited.
    $this->call('migrate', ['--force' => true]);

    // Passport: signing keys come from PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY env vars (a deploy disk is wiped
    // on every release). Only fall back to creating key files when neither is configured.
    if (!env('PASSPORT_PRIVATE_KEY') && !file_exists(storage_path('oauth-private.key'))) {
        $this->call('passport:keys', ['--force' => true]);
    }
    $hasPersonal = \Illuminate\Support\Facades\DB::table('oauth_clients')->where('grant_types', 'like', '%personal_access%')->exists();
    if (!$hasPersonal) {
        $this->call('passport:client', ['--personal' => true, '--name' => 'Personal Access Client', '--provider' => 'users', '--no-interaction' => true]);
    }

    if (!\App\Models\AdminUser::query()->exists()) {
        $this->call('db:seed', ['--class' => \Database\Seeders\AdminUserSeeder::class, '--force' => true]);
    }
    if (!\App\Models\Setting::query()->exists()) {
        $this->call('db:seed', ['--class' => \Database\Seeders\SettingsSeeder::class, '--force' => true]);
    }
    if (!\App\Models\NotificationTemplate::query()->exists()) {
        $this->call('db:seed', ['--class' => \Database\Seeders\NotificationTemplatesSeeder::class, '--force' => true]);
    }
    if (!\App\Models\AutoBlockRule::query()->exists()) {
        $this->call('db:seed', ['--class' => \Database\Seeders\AutoBlockRulesSeeder::class, '--force' => true]);
    }
    $this->info('Production setup done.');
})->purpose('Migrate + one-time essentials for a live server (no demo data)');





// ─── Scheduled Tasks ────────────────────────────────────────

Schedule::command('bazar:process-auto-blocks')->hourly();
Schedule::command('bazar:update-courier-status')->everyThirtyMinutes();
Schedule::command('bazar:daily-report')->dailyAt('23:55');
Schedule::command('bazar:cleanup-carts')->weekly();
Schedule::command('passport:purge')->daily();

// ─── Background jobs (no separate worker process needed) ────
// With QUEUE_CONNECTION=database/redis the only thing a server needs is the usual cron line:
//   * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
// Every minute this drains the queue and stops, so bulk notifications / block notifications are sent.
Schedule::call(fn() => \Illuminate\Support\Facades\Cache::put('scheduler_heartbeat', now()->timestamp, 3600))->everyMinute()->name('heartbeat');
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()->withoutOverlapping(5)->runInBackground()
    ->when(fn() => config('queue.default') !== 'sync');
Schedule::command('queue:prune-failed --hours=168')->daily();
