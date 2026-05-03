<?php

namespace App\Services\Notification;

use App\Services\Notification\Contracts\NotificationProviderInterface;
use App\Models\Notification;

class ChannelRouter
{
    public function __construct(
        private NotificationProviderInterface $provider
    ) {}

    public function send(Notification $notification, string $idempotencyKey): array
    {
        return $this->provider->send($notification, $idempotencyKey);
    }
}
