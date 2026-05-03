<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Jobs\ProcessNotificationJob;
use App\Services\Notification\ChannelRouter;
use App\Services\Notification\Contracts\NotificationRateLimiterInterface;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ProcessNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_method_processes_notification_successfully(): void
    {
        Log::spy();
        Date::setTestNow('2026-01-02 11:00:00');

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
            'channel' => NotificationChannel::SMS->value,
        ]);

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldReceive('send')
            ->once()
            ->andReturn([
                'success' => true,
                'messageId' => 'external-123',
            ]);

        $key = 'channel:' . NotificationChannel::SMS->value . ':messages';

        $rateLimiter = Mockery::mock(NotificationRateLimiterInterface::class);
        $rateLimiter->shouldReceive('tooMany')->once()->with($key)->andReturnFalse();
        $rateLimiter->shouldReceive('hit')->with($key)->once();

        $job = new ProcessNotificationJob($notification->id, NotificationChannel::SMS->value);
        $job->handle($router, $rateLimiter);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::SENT->value,
            'external_id' => 'external-123',
            'sent_at' => '2026-01-02 11:00:00',
        ]);

        Log::shouldHaveReceived('info')->with('Processing job', Mockery::type('array'));
        Log::shouldHaveReceived('info')->with('Processing notification', Mockery::type('array'));
    }

    public function test_handle_method_releases_job_when_rate_limited(): void
    {
        Log::spy();

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
            'channel' => NotificationChannel::SMS->value,
        ]);

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldNotReceive('send');

        $rateLimiter = Mockery::mock(NotificationRateLimiterInterface::class);
        $rateLimiter->shouldReceive('tooMany')->once()->andReturnTrue();

        $job = Mockery::mock(ProcessNotificationJob::class, [$notification->id, NotificationChannel::SMS->value,])->makePartial();

        $job->shouldReceive('release')->once()->with(1);

        $job->shouldReceive('hit')->never();

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldReceive('send')->never();

        $job->handle($router, $rateLimiter);

        Log::shouldHaveReceived('info')->with('Processing job', Mockery::type('array'));
        Log::shouldHaveReceived('warning')->with('Notification rate limit reached', Mockery::type('array'));
    }

    public function test_handle_method_retries_when_router_throws_exception(): void
    {
        Log::spy();

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
            'attempts' => 0,
        ]);

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('API down'));

        $rateLimiter = Mockery::mock(NotificationRateLimiterInterface::class);
        $rateLimiter->shouldReceive('tooMany')->once()->andReturnFalse();
        $rateLimiter->shouldReceive('hit')->once();

        $this->expectException(\Exception::class);

        $job = new ProcessNotificationJob($notification->id, $notification->channel);
        $job->handle($router, $rateLimiter);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'attempts' => 1,
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        Log::shouldHaveReceived('info')->with('Processing job', Mockery::type('array'));
        Log::shouldHaveReceived('info')->with('Processing notification', Mockery::type('array'));
        Log::shouldHaveReceived('warning')->with('Notification send failed, retrying', Mockery::type('array'));
    }

    public function test_handle_method_retries_when_response_is_unsuccessful(): void
    {
        Log::spy();

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
            'attempts' => 0,
        ]);

        $router = Mockery::mock(ChannelRouter::class);
        $router->shouldReceive('send')->once()->andReturn(['success' => false]);

        $rateLimiter = Mockery::mock(NotificationRateLimiterInterface::class);
        $rateLimiter->shouldReceive('tooMany')->once()->andReturnFalse();
        $rateLimiter->shouldReceive('hit')->once();

        $this->expectException(\Exception::class);

        $job = new ProcessNotificationJob($notification->id, $notification->channel);
        $job->handle($router, $rateLimiter);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'attempts' => 1,
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        Log::shouldHaveReceived('info')->with('Processing job', Mockery::type('array'));
        Log::shouldHaveReceived('info')->with('Processing notification', Mockery::type('array'));
        Log::shouldHaveReceived('warning')->with('Notification send failed response, retrying', Mockery::type('array'));
    }

    public function test_failed_method_marks_notification_as_failed(): void
    {
        Log::spy();

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        $job = new ProcessNotificationJob($notification->id, $notification->channel);

        $job->failed(new \Exception('Final failure'));

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::FAILED->value,
        ]);

        Log::shouldHaveReceived('error')->with('Notification job failed', Mockery::type('array'));
    }
}
