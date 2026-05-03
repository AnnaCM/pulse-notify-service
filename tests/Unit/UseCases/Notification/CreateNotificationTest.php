<?php

namespace Tests\Unit;

use App\UseCases\Notification\CreateNotification;
use App\DTOs\Notification\CreateNotificationDTO;
use App\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationPriority;
use App\Support\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_creates_notification_and_dispatches_job(): void
    {
        Date::setTestNow('2026-01-01 12:00:00');

        Queue::fake();

        $dto = new CreateNotificationDTO(
            channel: NotificationChannel::EMAIL->value,
            recipient: 'test@example.com',
            content: 'Hello world',
            priority: NotificationPriority::HIGH->value
        );

        $useCase = new CreateNotification();
        $notification = $useCase->execute($dto);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'channel' => NotificationChannel::EMAIL->value,
            'recipient' => 'test@example.com',
            'content' => 'Hello world',
            'priority' => NotificationPriority::HIGH->value,
            'status' => NotificationStatus::PENDING->value,
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-01-01 12:00:00',
        ]);

        $this->assertNotNull($notification->idempotency_key);

        Queue::assertPushed(ProcessNotificationJob::class, function ($job) use ($notification) {
            return $job->notificationId === $notification->id
                && $job->channel === NotificationChannel::EMAIL->value
                && $job->queue === NotificationPriority::HIGH->value;
        });
    }

    public function test_execute_method_generates_unique_ids(): void
    {
        Queue::fake();

        $dto = new CreateNotificationDTO(
            channel: NotificationChannel::EMAIL->value,
            recipient: 'a@test.com',
            content: 'A',
            priority: NotificationPriority::NORMAL->value,
        );

        $notification1 = (new CreateNotification())->execute($dto);
        $notification2 = (new CreateNotification())->execute($dto);

        $this->assertNotEquals($notification1->id, $notification2->id);
        $this->assertNotEquals($notification1->idempotency_key, $notification2->idempotency_key);
    }
}
