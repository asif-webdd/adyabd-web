<?php

namespace App\Console\Commands;

use App\Models\{AdminUser, Order, SmsLog, SupportTicket};
use App\Notifications\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedAdminNotifications extends Command
{
    protected $signature   = 'admin:seed-notifications {--fresh : Clear existing admin notifications first}';
    protected $description = 'Backfill admin notifications from existing orders, failed SMS, and open support tickets';

    public function handle(): int
    {
        $admins = AdminUser::where('is_active', true)->get();
        if ($admins->isEmpty()) {
            $this->error('No active admins found.');
            return self::FAILURE;
        }

        $this->info("Found {$admins->count()} active admin(s).");

        if ($this->option('fresh')) {
            DB::table('notifications')->where('notifiable_type', AdminUser::class)->delete();
            $this->warn('Cleared existing admin notifications.');
        }

        $orders = Order::latest()->limit(20)->get();
        $this->info("Seeding {$orders->count()} order notifications...");
        foreach ($orders as $o) {
            $n = new AdminNotification(
                title: 'Order',
                body:  "#{$o->order_number} · BDT " . number_format($o->total_amount, 0) . ' · ' . ucfirst($o->status),
                icon: 'shopping-bag', color: 'success',
                url:  "/admin/orders/{$o->id}",
                extra: ['order_id' => $o->id, 'order_number' => $o->order_number],
            );
            foreach ($admins as $admin) $admin->notify(clone $n);
        }

        $sms = SmsLog::where('status', 'failed')->latest()->limit(10)->get();
        $this->info("Seeding {$sms->count()} SMS failure notifications...");
        foreach ($sms as $s) {
            $n = new AdminNotification(
                title: 'SMS Failed',
                body:  "{$s->recipient_phone} · {$s->gateway}",
                icon: 'x-circle', color: 'danger',
                url:  '/admin/sms-logs',
                extra: ['sms_id' => $s->id],
            );
            foreach ($admins as $admin) $admin->notify(clone $n);
        }

        if (class_exists(\App\Models\SupportTicket::class)) {
            $tickets = SupportTicket::where('status', 'open')->latest()->limit(10)->get();
            $this->info("Seeding {$tickets->count()} ticket notifications...");
            foreach ($tickets as $t) {
                $n = new AdminNotification(
                    title: 'Support Ticket',
                    body:  $t->subject,
                    icon: 'chat-bubble-left-ellipsis', color: 'warning',
                    url:  "/admin/support-tickets/{$t->id}",
                    extra: ['ticket_id' => $t->id],
                );
                foreach ($admins as $admin) $admin->notify(clone $n);
            }
        }

        $total = DB::table('notifications')->where('notifiable_type', AdminUser::class)->count();
        $this->newLine();
        $this->info(" Done! Total admin notifications in DB: {$total}");
        $this->info('Refresh the admin panel — the bell will now show notifications.');
        return self::SUCCESS;
    }
}
