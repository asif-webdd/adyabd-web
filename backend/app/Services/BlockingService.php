<?php


namespace App\Services;

use App\Models\{IpBlock, User, AutoBlockRule, Order};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlockingService
{
    public function isIpBlocked(string $ip): bool
    {
        return IpBlock::active()->where('ip_address', $ip)->exists();
    }

    public function isUserBlocked(int $userId): bool
    {
        return (bool)User::find($userId)?->is_blocked;
    }

    public function blockUser(User $user, string $reason, ?int $adminId = null, bool $blockIp = true, ?string $duration = null): void
    {
        $user->update(['is_blocked' => true, 'blocked_reason' => $reason, 'block_type' => 'manual', 'blocked_at' => now(), 'blocked_by' => $adminId, 'status' => 'banned']);

        if ($blockIp && $user->last_login_ip) {
            IpBlock::updateOrCreate(['ip_address' => $user->last_login_ip], [
                'user_id'   => $user->id, 'reason' => $reason, 'type' => 'manual',
                'is_active' => true, 'expires_at' => $this->parseDuration($duration), 'blocked_by' => $adminId,
            ]);
        }

        // Cancel pending orders
        Order::where('user_id', $user->id)->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->each(fn($o) => $o->updateStatus('cancelled', 'User blocked: ' . $reason, 'system'));

        // SMS notification
        try {
            app(SmsService::class)->sendTemplate(
                'account_blocked', $user->phone, ['reason' => $reason], null,
                '{shop_name}: Your account has been suspended. Reason: {reason}. Support: {helpline}',
                'sms_on_user_blocked'
            );
        } catch (\Exception $e) {
        }

        Log::info("User {$user->id} blocked by admin {$adminId}. Reason: {$reason}");
    }

    public function unblockUser(User $user, ?int $adminId = null): void
    {
        $user->update(['is_blocked' => false, 'blocked_reason' => null, 'blocked_at' => null, 'blocked_by' => null, 'block_type' => null, 'status' => 'active']);
        IpBlock::where('user_id', $user->id)->update(['is_active' => false]);
        try {
            app(SmsService::class)->sendTemplate(
                'account_unblocked', $user->phone, [], null,
                '{shop_name}: Your account has been reactivated.',
                'sms_on_user_blocked'
            );
        } catch (\Exception $e) {
        }
        Log::info("User {$user->id} unblocked by admin {$adminId}");
    }

    public function blockIp(string $ip, string $reason, ?int $adminId = null, ?string $duration = null): IpBlock
    {
        $block = IpBlock::updateOrCreate(['ip_address' => $ip], ['reason' => $reason, 'type' => 'manual', 'is_active' => true, 'expires_at' => $this->parseDuration($duration), 'blocked_by' => $adminId]);
        $block->increment('block_count');
        return $block;
    }

    public function unblockIp(string $ip): void
    {
        IpBlock::where('ip_address', $ip)->update(['is_active' => false]);
    }

    public function handleOrderCancellation(Order $order): void
    {
        if ($order->cancelled_by !== 'user') return;
        if (!setting('auto_block_enabled', true)) return;
        $user = $order->user;
        $user->increment('cancel_order_count');
        $user->refresh();
        $rule = AutoBlockRule::where('trigger', 'cancel_order')->where('is_active', true)->first();
        if (!$rule || $user->is_blocked) return;
        $count = $rule->within_days
            ? Order::where('user_id', $user->id)->where('cancelled_by', 'user')->where('cancelled_at', '>=', now()->subDays($rule->within_days))->count()
            : $user->cancel_order_count;
        if ($count >= $rule->threshold) $this->blockUser($user, str_replace('{count}', $count, $rule->reason_template), null, $rule->block_ip_also && setting('auto_block_ip_enabled', true), $rule->block_duration);
    }

    public function handleFailedDelivery(Order $order, string $reason = 'failed_delivery'): void
    {
        if (!setting('auto_block_enabled', true)) return;
        $user = $order->user;
        $trigger = $reason === 'no_answer' ? 'no_answer' : 'failed_delivery';
        $trigger === 'no_answer' ? $user->increment('no_answer_count') : $user->increment('failed_delivery_count');
        $user->refresh();
        $rule = AutoBlockRule::where('trigger', $trigger)->where('is_active', true)->first();
        if (!$rule || $user->is_blocked) return;
        $count = $trigger === 'no_answer' ? $user->no_answer_count : $user->failed_delivery_count;
        if ($count >= $rule->threshold) $this->blockUser($user, str_replace('{count}', $count, $rule->reason_template), null, $rule->block_ip_also && setting('auto_block_ip_enabled', true), $rule->block_duration);
    }

    public function markNoAnswer(Order $order, string $note = ''): void
    {
        $order->updateStatus('out_for_delivery', 'Delivery attempted - no answer. ' . $note, 'admin');
        $this->handleFailedDelivery($order, 'no_answer');
        try {
            app(SmsService::class)->sendTemplate(
                'order_delivery_failed', $order->shipping_phone, app(SmsService::class)->orderVars($order), $order,
                '{shop_name}: Order #{order_number} could not be delivered. Please call us: {helpline}'
            );
        } catch (\Exception $e) {
        }
    }

    public function checkRequest(Request $request, ?int $userId = null): ?array
    {
        if ($this->isIpBlocked($request->ip())) return ['blocked' => true, 'reason' => 'ip_blocked', 'message' => 'Access from your IP has been restricted.'];
        if ($userId && $this->isUserBlocked($userId)) {
            $user = User::find($userId);
            return ['blocked' => true, 'reason' => 'user_blocked', 'message' => 'Your account has been suspended. Reason: ' . ($user?->blocked_reason ?? 'Violation of terms')];
        }
        return null;
    }

    private function parseDuration(?string $duration): ?\Carbon\Carbon
    {
        if (!$duration || $duration === 'permanent') return null;
        preg_match('/(\d+)\s*(day|days|month|months|year|years)/', $duration, $m);
        if (!$m) return null;
        $n = (int)$m[1];
        return str_contains($m[2], 'month') ? now()->addMonths($n) : (str_contains($m[2], 'year') ? now()->addYears($n) : now()->addDays($n));
    }
}
