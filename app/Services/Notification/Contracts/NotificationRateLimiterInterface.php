<?php

namespace App\Services\Notification\Contracts;

interface NotificationRateLimiterInterface
{
    public function tooMany(string $key, int $maxAttempts = 100): bool;
    public function hit(string $key, int $decay = 1): void;
    public function clear(string $key): void;
}
