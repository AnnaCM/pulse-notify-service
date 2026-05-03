<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use App\Support\Enums\NotificationStatus;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public string $batchId) {}

    public function handle()
    {
        Notification::where('batch_id', $this->batchId)
            ->where('status', NotificationStatus::PENDING->value)
            ->chunkById(100, function ($notifications) {
                foreach ($notifications as $notification) {
                    ProcessNotificationJob::dispatch(
                        $notification->id,
                        $notification->channel
                    )->onQueue($notification->priority);
                }
            });
    }
}
