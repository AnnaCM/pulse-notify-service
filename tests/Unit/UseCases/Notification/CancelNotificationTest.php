<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\UseCases\Notification\CancelNotification;
use App\Support\Enums\NotificationStatus;
use App\Exceptions\NotificationNotFoundException;
use App\Exceptions\CannotCancelNotificationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_cancels_a_pending_notification(): void
    {
        Log::spy();

        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
        ]);

        $useCase = new CancelNotification();
        $useCase->execute($notification->id);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::CANCELLED->value,
        ]);

        Log::shouldHaveReceived('info')->with('Cancelling notification', [
            'id' => $notification->id,
        ]);
    }

    public function test_execute_method_throws_exception_if_notification_not_found(): void
    {
        $this->expectException(NotificationNotFoundException::class);

        $id = (string) Str::uuid();

        $useCase = new CancelNotification();
        $useCase->execute($id);
    }

    public function test_execute_method_throws_exception_if_notification_is_not_pending(): void
    {
        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        $this->expectException(CannotCancelNotificationException::class);

        $useCase = new CancelNotification();
        $useCase->execute($notification->id);
    }

    public function test_execute_method_does_not_update_status_when_not_pending(): void
    {
        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PROCESSING->value,
        ]);

        try {
            (new CancelNotification())->execute($notification->id);
        } catch (CannotCancelNotificationException) {
            // expected
        }

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::PROCESSING->value,
        ]);
    }
}
