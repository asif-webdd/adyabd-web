<?php

namespace App\Jobs;

use App\Mail\BulkNotificationMail;
use App\Models\{BulkNotification, User, Seller};
use App\Services\SmsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 3600;

    public function __construct(public BulkNotification $notification)
    {
    }

    /**
     * @throws Exception
     */
    public function handle(SmsService $smsService): void
    {
        $n = $this->notification;
        $n->update(['status' => 'sending', 'started_at' => now()]);

        try {
            $recipients = $this->getRecipients($n);
            $n->update(['total_recipients' => count($recipients)]);

            $sent = $failed = 0;
            foreach (array_chunk($recipients, 100) as $chunk) {
                foreach ($chunk as $r) {
                    try {
                        if (in_array($n->channel, ['sms', 'all']) && !empty($r['phone'])) {
                            $smsService->send($r['phone'], $n->message) ? $sent++ : $failed++;
                        }
                        if (in_array($n->channel, ['email', 'all']) && !empty($r['email'])) {
                            Mail::to($r['email'])->queue(new BulkNotificationMail($n));
                            $sent++;
                        }
                        if (in_array($n->channel, ['push', 'all']) && !empty($r['fcm_token'])) {
                            $this->pushNotification($r['fcm_token'], $n->title, $n->message);
                        }
                    } catch (Exception $e) {
                        $failed++;
                        Log::warning("Bulk notif failed for {$r['phone']}: " . $e->getMessage());
                    }
                }
                $n->update(['sent_count' => $sent, 'failed_count' => $failed]);
                sleep(1); // Rate limiting
            }

            $n->update(['status' => 'completed', 'sent_count' => $sent, 'failed_count' => $failed, 'completed_at' => now()]);
            Log::info("Bulk notification {$n->id} completed: {$sent} sent, {$failed} failed");
        } catch (Exception $e) {
            $n->update(['status' => 'failed']);
            Log::error("Bulk notification {$n->id} failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function getRecipients(BulkNotification $n): array
    {
        if ($n->audience === 'sellers') {
            return Seller::where('status', 'active')->get(['phone', 'email'])->map(fn($s) => ['phone' => $s->phone, 'email' => $s->email, 'fcm_token' => null])->toArray();
        }

        $q = User::query();
        $q = match ($n->audience) {
            'active_users'   => $q->where('status', 'active')->where('is_blocked', false),
            'inactive_users' => $q->where('status', 'inactive'),
            default          => $q,
        };

        if (!empty($n->recipient_ids)) $q->whereIn('id', $n->recipient_ids);

        return $q->whereNotNull('phone')->get(['phone', 'email', 'fcm_token'])->map(fn($u) => [
            'phone'     => $u->phone,
            'email'     => $u->email,
            'fcm_token' => $u->fcm_token
        ])->toArray();
    }

    private function pushNotification(string $token, string $title, string $body): void
    {
        $key = config('services.firebase.server_key');
        if (!$key) return;
        Http::withHeaders(['Authorization' => 'key=' . $key, 'Content-Type' => 'application/json'])->post('https://fcm.googleapis.com/fcm/send', [
            'to'           => $token,
            'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default'],
            'data'         => ['type' => 'bulk', 'title' => $title, 'message' => $body],
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->notification->update(['status' => 'failed']);
    }
}
