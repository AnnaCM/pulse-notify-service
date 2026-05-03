<?php

namespace App\UseCases\Notification;

use App\Exceptions\NotificationNotFoundException;
use App\Models\Notification;

class GetNotification
{
    public function execute(string $id): Notification
    {
        $notification = Notification::find($id);
        if (!$notification) {
            throw new NotificationNotFoundException($id);
        }

        return $notification;
    }
}
