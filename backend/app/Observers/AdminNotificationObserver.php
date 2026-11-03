<?php

namespace App\Observers;

use App\Models\{ActivityLog, AdminUser};
use App\Notifications\AdminNotification;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Single observer wired to all tracked models.
 * Handles: 1) admin bell notifications  2) activity log
 */
class AdminNotificationObserver
{
    // ── Notification config ───────────────────────────────────────────────
    private function notifConfig(Model $model, string $event): ?array
    {
        $c = class_basename($model);

        return match (true) {
            // Orders
            $c === 'Order' && $event === 'created' => [
                'title' => 'New Order',
                'body'  => "#{$model->order_number} · BDT " . number_format($model->total_amount, 0) . " · {$model->shipping_name}",
                'icon'  => 'shopping-bag', 'color' => 'success',
                'url'   => "/admin/orders/{$model->id}",
                'extra' => ['order_id' => $model->id, 'order_number' => $model->order_number],
            ],
            $c === 'Order' && $event === 'updated' && $model->isDirty('status') => [
                'title' => 'Order Status',
                'body'  => "#{$model->order_number} → " . ucfirst(str_replace('_', ' ', $model->status)),
                'icon'  => 'arrow-path',
                'color' => match ($model->status) {
                    'delivered' => 'success', 'cancelled', 'returned' => 'danger', default => 'info'
                },
                'url'   => "/admin/orders/{$model->id}",
                'extra' => ['order_id' => $model->id, 'status' => $model->status],
            ],
            // Products
            $c === 'Product' && $event === 'created' => [
                'title' => 'New Product',
                'body'  => "\"{$model->name}\" · BDT " . number_format($model->price, 0),
                'icon'  => 'archive-box', 'color' => 'info',
                'url'   => "/admin/products/{$model->id}",
                'extra' => ['product_id' => $model->id],
            ],
            $c === 'Product' && $event === 'updated' && $model->isDirty('is_active') => [
                'title' => $model->is_active ? 'Product Active' : 'Product Inactive',
                'body'  => "\"{$model->name}\"",
                'icon'  => 'archive-box',
                'color' => $model->is_active ? 'success' : 'warning',
                'url'   => "/admin/products/{$model->id}",
                'extra' => ['product_id' => $model->id],
            ],
            // Sellers
            $c === 'Seller' && $event === 'created' => [
                'title' => 'New Seller',
                'body'  => "\"{$model->shop_name}\"",
                'icon'  => 'building-storefront', 'color' => 'info',
                'url'   => "/admin/sellers/{$model->id}",
                'extra' => ['seller_id' => $model->id],
            ],
            $c === 'Seller' && $event === 'updated' && $model->isDirty('status') => [
                'title' => 'Seller Status',
                'body'  => "\"{$model->shop_name}\" → " . ucfirst($model->status),
                'icon'  => 'building-storefront',
                'color' => $model->status === 'active' ? 'success' : 'warning',
                'url'   => "/admin/sellers/{$model->id}",
                'extra' => ['seller_id' => $model->id],
            ],
            // Users
            $c === 'User' && $event === 'created' => [
                'title' => 'New User',
                'body'  => ($model->name ?? $model->phone) . " has registered",
                'icon'  => 'user-plus', 'color' => 'info',
                'url'   => "/admin/users/{$model->id}",
                'extra' => ['user_id' => $model->id],
            ],
            $c === 'User' && $event === 'updated' && $model->isDirty('is_blocked') => [
                'title' => $model->is_blocked ? 'User Blocked' : 'Unblocked',
                'body'  => ($model->name ?? $model->phone),
                'icon'  => 'user',
                'color' => $model->is_blocked ? 'danger' : 'success',
                'url'   => "/admin/users/{$model->id}",
                'extra' => ['user_id' => $model->id],
            ],
            // Support Tickets
            $c === 'SupportTicket' && $event === 'created' => [
                'title' => 'New Ticket',
                'body'  => $model->subject,
                'icon'  => 'chat-bubble-left-ellipsis', 'color' => 'warning',
                'url'   => "/admin/support-tickets/{$model->id}",
                'extra' => ['ticket_id' => $model->id],
            ],
            // Reviews
            $c === 'Review' && $event === 'created' => [
                'title' => 'New Review',
                'body'  => "Rating: {$model->rating}/5 · " . Str::limit($model->comment ?? '', 50),
                'icon'  => 'star',
                'color' => $model->rating >= 4 ? 'success' : ($model->rating <= 2 ? 'danger' : 'warning'),
                'url'   => "/admin/reviews/{$model->id}",
                'extra' => ['review_id' => $model->id],
            ],
            // Flash Sales
            $c === 'FlashSale' && $event === 'created' => [
                'title' => 'New Flash Sale',
                'body'  => $model->title,
                'icon'  => 'bolt', 'color' => 'warning',
                'url'   => "/admin/flash-sales/{$model->id}",
                'extra' => ['flash_sale_id' => $model->id],
            ],
            // Coupons
            $c === 'Coupon' && $event === 'created' => [
                'title' => 'New Coupon',
                'body'  => "Code: {$model->code}",
                'icon'  => 'ticket', 'color' => 'success',
                'url'   => "/admin/coupons/{$model->id}",
                'extra' => ['coupon_id' => $model->id],
            ],
            // SMS failures
            $c === 'SmsLog' && $event === 'created' && ($model->status ?? '') === 'failed' => [
                'title' => 'SMS Failed',
                'body'  => "{$model->recipient_phone} · {$model->gateway}",
                'icon'  => 'x-circle', 'color' => 'danger',
                'url'   => '/admin/sms-logs',
                'extra' => ['sms_id' => $model->id],
            ],
            // Leave requests
            $c === 'Leave' && $event === 'created' => [
                'title' => 'Leave Request',
                'body'  => "Employee #{$model->employee_id} · {$model->leave_type}",
                'icon'  => 'calendar', 'color' => 'info',
                'url'   => "/admin/leaves/{$model->id}",
                'extra' => ['leave_id' => $model->id],
            ],

            default => null,
        };
    }

    // ── Activity log action label ─────────────────────────────────────────
    private function actionLabel(Model $model, string $event): string
    {
        $c = class_basename($model);
        $id = $model->getKey();

        if ($event === 'created') return "Created {$c} #{$id}";
        if ($event === 'deleted') return "Deleted {$c} #{$id}";
        if ($event === 'restored') return "Restored {$c} #{$id}";

        // Updated — be specific about what changed
        $dirty = array_keys($model->getDirty());
        if (empty($dirty)) return "Updated {$c} #{$id}";

        $labels = [
            'status'       => 'Status → ' . ($model->status ?? ''),
            'payment_status' => 'Payment → ' . ($model->payment_status ?? ''),
            'is_active'    => ($model->is_active ? 'Activated' : 'Deactivated') . " {$c}",
            'is_blocked'   => ($model->is_blocked ? 'Blocked' : 'Unblocked') . " {$c}",
            'is_verified'  => ($model->is_verified ? 'Verified' : 'Unverified') . " {$c}",
        ];

        foreach ($labels as $field => $label) {
            if (in_array($field, $dirty)) return $label . " #{$id}";
        }

        return "Updated " . implode(', ', $dirty) . " on {$c} #{$id}";
    }

    // ── Observer hooks ────────────────────────────────────────────────────
    public function created(Model $model): void
    {
        $this->doLog($model, 'created');
        $this->notify($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->doLog($model, 'updated');
        $this->notify($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->doLog($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->doLog($model, 'restored');
    }

    // ── Internals ─────────────────────────────────────────────────────────
    private function notify(Model $model, string $event): void
    {
        $cfg = $this->notifConfig($model, $event);
        if (! $cfg) return;

        $admins = AdminUser::where('is_active', true)->get();
        if ($admins->isEmpty()) return;

        $notif = new AdminNotification(
            title: $cfg['title'],
            body:  $cfg['body'],
            icon:  $cfg['icon'],
            color: $cfg['color'],
            url:   $cfg['url'] ?? null,
            extra: $cfg['extra'] ?? [],
        );

        foreach ($admins as $admin) {
            $admin->notify(clone $notif);
        }
    }

    private function doLog(Model $model, string $event): void
    {
        $dirty = $model->getDirty();
        $old   = [];

        if ($event === 'updated' && $dirty) {
            foreach (array_keys($dirty) as $k) {
                // Skip noisy timestamp-only updates
                if (in_array($k, ['updated_at'])) continue;
                $old[$k] = $model->getOriginal($k);
            }
            // Remove updated_at from dirty too
            unset($dirty['updated_at']);
            if (empty($dirty)) return; // nothing meaningful changed
        }

        $properties = match ($event) {
            'created'  => ['attributes' => $this->safeAttributes($model)],
            'updated'  => $old ? ['old' => $old, 'attributes' => $dirty] : [],
            default    => [],
        };

        ActivityLogger::log(
            subject:    $model,
            event:      $event,
            action:     $this->actionLabel($model, $event),
            properties: $properties,
        );
    }

    private function safeAttributes(Model $model): array
    {
        // Exclude sensitive fields from the log
        $hidden = ['password', 'remember_token', 'api_token'];
        return array_diff_key($model->getAttributes(), array_flip($hidden));
    }
}
