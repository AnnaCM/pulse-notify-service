<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Notification;
use App\Jobs\ProcessBatchNotificationJob;
use App\Jobs\ProcessNotificationJob;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationStatus;
use App\Support\Enums\NotificationPriority;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessBatchNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_method_dispatches_jobs_for_pending_notifications_in_batch(): void
    {
        Queue::fake();

        $batchId = (string) Str::uuid();

        $pendingNotifications = Notification::factory()->count(3)->create([
            'batch_id' => $batchId,
            'status' => NotificationStatus::PENDING->value,
            'priority' => NotificationPriority::LOW->value,
            'channel' => NotificationChannel::SMS->value,
        ]);

        Notification::factory()->create([
            'batch_id' => $batchId,
            'status' => NotificationStatus::SENT->value,
        ]);

        Notification::factory()->create([
            'batch_id' => (string) Str::uuid(),
            'status' => NotificationStatus::PENDING->value,
        ]);

        $job = new ProcessBatchNotificationJob($batchId);
        $job->handle();

        Queue::assertPushed(ProcessNotificationJob::class, count($pendingNotifications));

        Queue::assertPushed(ProcessNotificationJob::class, function ($job) use ($pendingNotifications) {
            return in_array($job->notificationId, $pendingNotifications->pluck('id')->toArray())
                && $job->channel === NotificationChannel::SMS->value
                && $job->queue === NotificationPriority::LOW->value;
        });
    }
}
