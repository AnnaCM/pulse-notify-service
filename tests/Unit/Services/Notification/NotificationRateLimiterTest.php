<?php

namespace Tests\Unit;

use App\Services\Notification\NotificationRateLimiter;
use Tests\TestCase;

class NotificationRateLimiterTest extends TestCase
{
    public function test_rate_limiter_blocks_after_limit(): void
    {
        $limiter = new NotificationRateLimiter();

        $key = 'test-key';

        $limiter->clear($key);

        for ($i = 0; $i < 100; $i++) {
            $limiter->hit($key);
        }

        $this->assertTrue($limiter->tooMany($key));
    }

    public function test_rate_limiter_allows_under_limit(): void
    {
        $limiter = new NotificationRateLimiter();

        $key = 'test-key';

        $limiter->clear($key);

        for ($i = 0; $i < 10; $i++) {
            $limiter->hit($key);
        }

        $this->assertFalse($limiter->tooMany($key));
    }
}
