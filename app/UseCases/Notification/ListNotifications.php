<?php

namespace App\UseCases\Notification;

use App\DTOs\Notification\ListNotificationsDTO;
use App\Models\Notification;
use Illuminate\Pagination\CursorPaginator;

class ListNotifications
{
    public function execute(ListNotificationsDTO $dto): CursorPaginator
    {
        $query = Notification::query();

        if ($dto->status) {
            $query->where('status', $dto->status);
        }

        if ($dto->channel) {
            $query->where('channel', $dto->channel);
        }

        if ($dto->from) {
            $query->where('created_at', '>=', $dto->from);
        }

        if ($dto->to) {
            $query->where('created_at', '<=', $dto->to);
        }

        return $query->orderByDesc('created_at')
                     ->cursorPaginate($dto->perPage)
                     ->withQueryString();
    }
}
