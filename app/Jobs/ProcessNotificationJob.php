<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Notification\ChannelRouter;
use App\Services\Notification\Contracts\NotificationRateLimiterInterface;
use App\Support\Enums\NotificationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;
    
    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly string $notificationId,
        public readonly string $channel
    ) {}

    public function handle(
        ChannelRouter $router,
        NotificationRateLimiterInterface $rateLimiter
    ): void {
        Log::info('Processing job', [
            'id' => $this->notificationId,
        ]);

        $key = "channel:{$this->channel}:messages";

        if ($rateLimiter->tooMany($key)) {
            Log::warning('Notification rate limit reached', [
                'key' => $key,
                'notification_id' => $this->notificationId,
                'attempt' => $this->attempts(),
            ]);

            $this->release(1);

            return;
        }

        $rateLimiter->hit($key);

        $notification = Notification::where('id', $this->notificationId)
            ->where('status', NotificationStatus::PENDING->value)
            ->first();

        if (!$notification) return;

        $notification->update([
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        try {
            Log::info('Processing notification', [
                'notification_id' => $this->notificationId,
                'correlation_id' => $notification->idempotency_key,
            ]);

            $result = $router->send($notification, $notification->idempotency_key);
        } catch (\Throwable $e) {
            $notification->increment('attempts');
            Log::warning('Notification send failed, retrying', [
                'notification_id' => $notification->id,
                'attempt' => $notification->attempts + 1,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!($result['success'] ?? false)) {
            $notification->increment('attempts');
            Log::warning('Notification send failed response, retrying', [
                'notification_id' => $notification->id,
                'attempt' => $notification->attempts + 1,
            ]);
            throw new \Exception('Delivery failed');
        }

        $notification->update([
            'status' => NotificationStatus::SENT->value,
            'sent_at' => now(),
            'external_id' => $result['messageId'] ?? null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Notification::where('id', $this->notificationId)
            ->update([
                'status' => NotificationStatus::FAILED->value,
            ]);

        Log::error('Notification job failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
