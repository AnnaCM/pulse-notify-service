<?php

namespace App\DTOs\Notification;

use App\DTOs\Notification\CreateNotificationDTO;
use App\Support\Enums\NotificationPriority;

class CreateBatchNotificationDTO
{
    /**
     * @param CreateNotificationDTO[] $notifications
     */
    public function __construct(
        public readonly array $notifications,
        public readonly string $priority = NotificationPriority::NORMAL->value,
    ) {}
}
