<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Support\Enums\NotificationStatus;
use App\Support\Enums\NotificationPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MetricsApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_metrics_endpoint_returns_correct_stats(): void
    {
        Redis::shouldReceive('llen')
            ->with('queues:' . NotificationPriority::HIGH->value)
            ->andReturn(5);
        Redis::shouldReceive('llen')
            ->with('queues:' . NotificationPriority::NORMAL->value)
            ->andReturn(3);
        Redis::shouldReceive('llen')
            ->with('queues:' . NotificationPriority::LOW->value)
            ->andReturn(2);

        Notification::factory()->count(2)->create(['status' => NotificationStatus::PENDING]);
        Notification::factory()->count(1)->create(['status' => NotificationStatus::PROCESSING]);
        Notification::factory()->count(4)->create(['status' => NotificationStatus::SENT]);
        Notification::factory()->count(1)->create(['status' => NotificationStatus::FAILED]);
        Notification::factory()->count(2)->create(['status' => NotificationStatus::CANCELLED]);

        $response = $this->getJson('/api/metrics');

        $response->assertStatus(200)
            ->assertJson([
                'queue' => [
                    NotificationPriority::HIGH->value => 5,
                    NotificationPriority::NORMAL->value => 3,
                    NotificationPriority::LOW->value => 2,
                ],
                'notifications' => [
                    'healthy' => true,
                    'total' => 10,
                    'pending' => 2,
                    'processing' => 1,
                    'sent' => 4,
                    'failed' => 1,
                    'cancelled' => 2,
                    'success_rate' => round((4 / (4+1+2))*100, 2),
                    'completion_rate' => round((4+1+2)/10*100, 2),
                ],
            ]);
    }

    public function test_metrics_endpoint_returns_error_when_redis_fails(): void
    {
        Notification::factory()->createMany([
            ['status' => NotificationStatus::PENDING->value],
            ['status' => NotificationStatus::PROCESSING->value],
            ['status' => NotificationStatus::SENT->value],
            ['status' => NotificationStatus::FAILED->value],
            ['status' => NotificationStatus::CANCELLED->value],
        ]);

        Redis::shouldReceive('llen')->andThrow(new \Exception('Redis down'));

        $response = $this->getJson('/api/metrics');

        $response->assertOk()
            ->assertJson([
                'queue' => [
                    'healthy' => false,
                    NotificationPriority::HIGH->value => 0,
                    NotificationPriority::NORMAL->value => 0,
                    NotificationPriority::LOW->value => 0,
                ],
                'notifications' => [
                    'healthy' => true,
                ],
            ]);
    }
}
