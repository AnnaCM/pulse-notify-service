<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\UseCases\Notification\GetNotification;
use App\Exceptions\NotificationNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_returns_a_notification_when_it_exists(): void
    {
        $notification = Notification::factory()->create();

        $useCase = new GetNotification();
        $result = $useCase->execute($notification->id);

        $this->assertEquals($notification->id, $result->id);
        $this->assertEquals($notification->channel, $result->channel);
        $this->assertEquals($notification->recipient, $result->recipient);
        $this->assertEquals($notification->status, $result->status);
    }

    public function test_execute_method_throws_exception_when_notification_not_found(): void
    {
        $this->expectException(NotificationNotFoundException::class);
        $this->expectExceptionMessage('Notification with id non-existent-id not found');

        $useCase = new GetNotification();
        $useCase->execute('non-existent-id');
    }
}
