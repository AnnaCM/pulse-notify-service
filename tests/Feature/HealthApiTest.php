<?php

namespace Tests\Feature;

use App\Support\Enums\NotificationPriority;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_endpoint_returns_all_services_healthy(): void
    {
        Date::setTestNow('2026-01-01 12:00:00');

        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('ping')
            ->once()
            ->andReturn(true);

        Redis::shouldReceive('connection->llen')
            ->andReturn(10);

        $response = $this->getJson('/api/health-check');

        $response->assertOk()
            ->assertJson([
                'app' => 'ok',
                'db' => true,
                'redis' => true,
            ])
            ->assertJsonStructure([
                'queue',
                'timestamp',
            ]);

        $this->assertEquals(
            '2026-01-01T12:00:00.000000Z',
            $response->json('timestamp')
        );

        foreach (NotificationPriority::cases() as $priority) {
            $this->assertArrayHasKey($priority->value, $response->json('queue'));
        }
    }

    public function test_health_endpoint_handles_failures(): void
    {
        Date::setTestNow('2026-01-01 12:00:00');

        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \Exception('DB down'));

        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Redis down'));

        Redis::shouldReceive('connection->llen')
            ->andThrow(new \Exception('Redis queue down'));

        $response = $this->getJson('/api/health-check');

        $response->assertOk()
            ->assertJson([
                'app' => 'ok',
                'db' => false,
                'redis' => false,
            ])
            ->assertJsonFragment([
                'queue' => [
                    'healthy' => false,
                ],
            ]);

        $this->assertEquals(
            '2026-01-01T12:00:00.000000Z',
            $response->json('timestamp')
        );
    }
}
