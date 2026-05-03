<?php

namespace App\UseCases\Notification;

use App\Exceptions\BatchNotificationNotFoundException;
use App\Models\Notification;
use Illuminate\Pagination\CursorPaginator;

class GetBatchNotification
{
    const LIMIT = 20;

    public function execute(string $batchId): CursorPaginator
    {
        return Notification::where('batch_id', $batchId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(self::LIMIT);
    }
}
