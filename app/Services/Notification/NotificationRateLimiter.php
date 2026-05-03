<?php

namespace App\Services\Notification;

use App\Services\Notification\Contracts\NotificationRateLimiterInterface;
use Illuminate\Support\Facades\RateLimiter;

class NotificationRateLimiter implements NotificationRateLimiterInterface
{
    public function tooMany(string $key, int $maxAttempts = 100): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    public function hit(string $key, int $decay = 1): void
    {
        RateLimiter::hit($key, $decay);
    }

    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }
}
