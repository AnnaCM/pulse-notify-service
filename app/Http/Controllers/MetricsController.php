<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\Enums\NotificationPriority;
use App\Support\Enums\NotificationStatus;
use Illuminate\Support\Facades\Redis;

class MetricsController
{
    public function __invoke()
    {
        return response()->json([
            'queue' => $this->queueDepth(),
            'notifications' => $this->stats(),
        ]);
    }

    private function queueDepth(): array
    {
        try {
            return [
                'healthy' => 'false',
                NotificationPriority::HIGH->value => Redis::llen('queues:' . NotificationPriority::HIGH->value),
                NotificationPriority::NORMAL->value => Redis::llen('queues:' . NotificationPriority::NORMAL->value),
                NotificationPriority::LOW->value => Redis::llen('queues:' . NotificationPriority::LOW->value),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                NotificationPriority::HIGH->value => 0,
                NotificationPriority::NORMAL->value => 0,
                NotificationPriority::LOW->value => 0,
            ];
        }
    }

    private function stats(): array
    {
        try {
            $total = Notification::count();

            $pending = Notification::where('status', NotificationStatus::PENDING->value)->count();
            $processing = Notification::where('status', NotificationStatus::PROCESSING->value)->count();
            $sent = Notification::where('status', NotificationStatus::SENT->value)->count();
            $failed = Notification::where('status', NotificationStatus::FAILED->value)->count();
            $cancelled = Notification::where('status', NotificationStatus::CANCELLED->value)->count();

            $completed = $sent + $failed + $cancelled;

            return [
                'healthy' => true,
                'total' => $total,

                'pending' => $pending,
                'processing' => $processing,

                'sent' => $sent,
                'failed' => $failed,
                'cancelled' => $cancelled,

                'success_rate' => $completed ? round(($sent / $completed) * 100, 2) : 0,

                'completion_rate' => $total ? round(($completed / $total) * 100, 2) : 0
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0,
                'cancelled' => 0,
                'success_rate' => 0,
                'completion_rate' => 0,
            ];
        }
    }
}
