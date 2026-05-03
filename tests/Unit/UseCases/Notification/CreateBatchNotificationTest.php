<?php

namespace Tests\Unit;

use App\UseCases\Notification\CreateBatchNotification;
use App\DTOs\Notification\CreateBatchNotificationDTO;
use App\DTOs\Notification\CreateNotificationDTO;
use App\Exceptions\EmptyBatchException;
use App\Jobs\ProcessBatchNotificationJob;
use App\Models\Notification;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationPriority;
use App\Support\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateBatchNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_creates_a_batch_of_notifications_and_dispatches_job(): void
    {
        Date::setTestNow('2026-01-01 15:00:00');

        Queue::fake();

        $notifications = [
            new CreateNotificationDTO(channel: NotificationChannel::EMAIL->value, recipient: 'user1@example.com', content: 'Hello 1', priority: NotificationPriority::HIGH->value),
            new CreateNotificationDTO(channel: NotificationChannel::SMS->value, recipient: '1111111111', content: 'Hello 2', priority: NotificationPriority::LOW->value),
            new CreateNotificationDTO(channel: NotificationChannel::EMAIL->value, recipient: 'user3@example.com', content: 'Hello 3', priority: NotificationPriority::NORMAL->value),
        ];

        $dto = new CreateBatchNotificationDTO(
            notifications: $notifications,
            priority: NotificationPriority::NORMAL->value
        );

        $useCase = new CreateBatchNotification();
        $batchId = $useCase->execute($dto);

        $this->assertDatabaseCount('notifications', 3);

        foreach ($notifications as $item) {
            $this->assertDatabaseHas('notifications', [
                'channel' => $item->channel,
                'recipient' => $item->recipient,
                'content' => $item->content,
                'status' => NotificationStatus::PENDING->value,
                'priority' => $item->priority,
                'batch_id' => $batchId,
                'created_at' => '2026-01-01 15:00:00',
                'updated_at' => '2026-01-01 15:00:00',
            ]);
        }

        Queue::assertPushed(ProcessBatchNotificationJob::class, function ($job) use ($batchId) {
            return $job->batchId === $batchId;
        });
    }

    public function test_execute_method_creates_empty_batch_returns_batch_id(): void
    {
        Queue::fake();

        $dto = new CreateBatchNotificationDTO(
            notifications: [],
            priority: NotificationPriority::NORMAL->value
        );

        $this->expectException(EmptyBatchException::class);

        $useCase = new CreateBatchNotification();
        $useCase->execute($dto);

        $this->assertDatabaseCount('notifications', 0);

        Queue::assertNothingPushed();
    }
}
