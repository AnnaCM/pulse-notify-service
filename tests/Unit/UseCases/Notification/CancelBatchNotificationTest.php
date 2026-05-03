<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\UseCases\Notification\CancelBatchNotification;
use App\Support\Enums\NotificationStatus;
use App\Exceptions\BatchNotificationNotFoundException;
use App\Exceptions\NoPendingNotificationsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelBatchNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_cancels_pending_notifications(): void
    {
        $batchId = (string) Str::uuid();

        Notification::factory()->create([
            'batch_id' => $batchId,
            'status' => NotificationStatus::PENDING->value,
        ]);

        Notification::factory()->create([
            'batch_id' => $batchId,
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        $useCase = new CancelBatchNotification();
        $useCase->execute($batchId);

        $this->assertDatabaseCount('notifications', 2);

        $this->assertDatabaseHas('notifications', [
            'batch_id' => $batchId,
            'status' => NotificationStatus::CANCELLED->value,
        ]);

        $this->assertDatabaseHas('notifications', [
            'batch_id' => $batchId,
            'status' => NotificationStatus::PROCESSING->value,
        ]);
    }

    public function test_execute_method_throws_exception_if_batch_not_found(): void
    {
        $this->expectException(BatchNotificationNotFoundException::class);

        $batchId = (string) Str::uuid();

        $useCase = new CancelBatchNotification();
        $useCase->execute($batchId);
    }

    public function test_execute_method_throws_exception_if_no_pending_notifications(): void
    {
        $batchId = (string) Str::uuid();

        Notification::factory()->create([
            'batch_id' => $batchId,
            'status' => NotificationStatus::SENT->value,
        ]);

        $this->expectException(NoPendingNotificationsException::class);

        $useCase = new CancelBatchNotification();
        $useCase->execute($batchId);
    }
}
