<?php

namespace App\DTOs\Notification;

use App\Support\Enums\NotificationPriority;

class CreateNotificationDTO
{
    public function __construct(
        public readonly string $channel,
        public readonly string $recipient,
        public readonly string $content,
        public readonly string $priority = NotificationPriority::NORMAL->value,
    ) {}
}
