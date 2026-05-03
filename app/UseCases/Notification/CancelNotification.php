<?php

namespace App\UseCases\Notification;

use App\Exceptions\CannotCancelNotificationException;
use App\Exceptions\NotificationNotFoundException;
use App\Models\Notification;
use App\Support\Enums\NotificationStatus;
use Illuminate\Support\Facades\Log;

class CancelNotification
{
    public function execute(string $id): void
    {
        $notification = Notification::find($id);
        if (!$notification) {
            throw new NotificationNotFoundException($id);
        }

        if ($notification->status !== NotificationStatus::PENDING->value) {
            throw new CannotCancelNotificationException();
        }

        Log::info('Cancelling notification', [
            'id' => $id,
        ]);

        $notification->update([
            'status' => NotificationStatus::CANCELLED->value
        ]);
    }
}
