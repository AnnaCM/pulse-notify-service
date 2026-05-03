<?php

namespace App\DTOs\Notification;

class ListNotificationsDTO
{
    public function __construct(
        public readonly ?string $status,
        public readonly ?string $channel,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly int $perPage = 20
    ) {}
}
