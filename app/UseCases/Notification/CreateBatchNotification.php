<?php

namespace App\UseCases\Notification;

use App\DTOs\Notification\CreateBatchNotificationDTO;
use App\DTOs\Notification\CreateNotificationDTO;
use App\Exceptions\EmptyBatchException;
use App\Jobs\ProcessBatchNotificationJob;
use App\Models\Notification;
use App\Support\Enums\NotificationStatus;
use Illuminate\Support\Str;

class CreateBatchNotification
{
    public function execute(CreateBatchNotificationDTO $dto): string
    {
        if (empty($dto->notifications)) {
            throw new EmptyBatchException();
        }

        $batchId = (string) Str::uuid();

        $now = now();

        $data = array_map(function (CreateNotificationDTO $item) use ($batchId, $now) {
            return [
                'id' => (string) Str::uuid(),
                'batch_id' => $batchId,
                'channel' => $item->channel,
                'recipient' => $item->recipient,
                'content' => $item->content,
                'priority' => $item->priority,
                'status' => NotificationStatus::PENDING->value,
                'idempotency_key' => (string) Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $dto->notifications);

        Notification::insert($data);

        ProcessBatchNotificationJob::dispatch($batchId);

        return $batchId;
    }
}
