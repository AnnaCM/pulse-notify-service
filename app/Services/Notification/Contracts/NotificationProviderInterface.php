<?php

namespace App\Services\Notification\Contracts;

use App\Models\Notification;

interface NotificationProviderInterface
{
    public function send(Notification $notification, string $idempotencyKey): array;
}
