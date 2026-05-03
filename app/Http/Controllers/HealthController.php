<?php

namespace App\Http\Controllers;

use App\Support\Enums\NotificationPriority;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController
{
    public function __invoke()
    {
        return response()->json([
            'app' => 'ok',
            'db' => $this->checkDb(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function checkDb(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueue(): array
    {
        try {
            $queues = array_column(NotificationPriority::cases(), 'value');

            $status = [];

            foreach ($queues as $queue) {
                $length = Redis::connection()->llen("queues:{$queue}");

                $status[$queue] = [
                    'size' => $length,
                    'healthy' => true,
                ];
            }

            return $status;

        } catch (\Throwable $e) {
            return [
                'healthy' => false,
            ];
        }
    }
}
