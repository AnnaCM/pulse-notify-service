<?php

namespace App\Services\Notification\Drivers;

use App\Models\Notification;
use App\Services\Notification\Contracts\NotificationProviderInterface;
use Illuminate\Support\Facades\Http;

class WebhookSite implements NotificationProviderInterface
{
    public function send(
        Notification $notification,
        string $idempotencyKey
    ): array {
        $url = config('services.webhook.url');

        if (!$url) {
            throw new \RuntimeException('Webhook URL not configured');
        }

        $response = Http::post($url, [
            'to' => $notification->recipient,
            'channel' => $notification->channel,
            'content' => $notification->content,
            'correlation_id' => $idempotencyKey,
        ]);

        $data = json_decode($response->body(), true);
        return [
            'success' => $response->status() === 202,
            'messageId' => $data['messageId'] ?? null,
            'raw' => $data,
        ];
    }
}
