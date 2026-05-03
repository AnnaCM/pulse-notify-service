<?php

namespace App\UseCases\Notification;

use App\DTOs\Notification\CreateNotificationDTO;
use App\Jobs\ProcessNotificationJob;
use App\Models\Notification as NotificationModel;
use App\Support\Enums\NotificationStatus;
use Illuminate\Support\Str;

class CreateNotification
{
    public function execute(CreateNotificationDTO $dto): NotificationModel
    {
        $now = now();
        $notification = NotificationModel::create([
            'id' => (string) Str::uuid(),
            'channel' => $dto->channel,
            'recipient' => $dto->recipient,
            'content' => $dto->content,
            'priority' => $dto->priority,
            'status' => NotificationStatus::PENDING->value,
            'idempotency_key' => (string) Str::uuid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        ProcessNotificationJob::dispatch(
            $notification->id,
            $notification->channel
        )->onQueue($dto->priority);

        return $notification;
    }
}
