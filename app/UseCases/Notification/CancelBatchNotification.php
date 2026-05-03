<?php

namespace App\UseCases\Notification;

use App\Exceptions\BatchNotificationNotFoundException;
use App\Exceptions\NoPendingNotificationsException;
use App\Models\Notification;
use App\Support\Enums\NotificationStatus;

class CancelBatchNotification
{
    public function execute(string $batchId): void
    {
       $query = Notification::where('batch_id', $batchId);

        if (!$query->exists()) {
            throw new BatchNotificationNotFoundException($batchId);
        }

        $updatedCount = $query
            ->where('status', NotificationStatus::PENDING->value)
            ->update([
                'status' => NotificationStatus::CANCELLED->value
            ]);

        if ($updatedCount === 0) {
            throw new NoPendingNotificationsException($batchId);
        }
    }
}
