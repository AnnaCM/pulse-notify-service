<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessBatchNotificationJob;
use App\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationPriority;
use App\Support\Enums\NotificationStatus;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_method_creates_a_single_notification(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/notifications', [
            'channel' => NotificationChannel::EMAIL->value,
            'recipient' => 'test@example.com',
            'content' => 'Hello world',
            'priority' => NotificationPriority::NORMAL->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'content']
            ]);

        $this->assertDatabaseHas('notifications', [
            'channel' => NotificationChannel::EMAIL->value,
            'recipient' => 'test@example.com',
            'content' => 'Hello world',
            'status' => NotificationStatus::PENDING->value,
            'priority' => NotificationPriority::NORMAL->value,
        ]);

        Queue::assertPushed(ProcessNotificationJob::class, function ($job) {
            return $job->queue === NotificationPriority::NORMAL->value
                && $job->notificationId !== null
                && $job->channel === NotificationChannel::EMAIL->value;
        });
    }

    public function test_batch_method_creates_and_processes_batch_notifications(): void
    {
        Queue::fake();

        $batchPayload = [
            'priority' => NotificationPriority::LOW->value,
            'notifications' => [
                ['channel' => NotificationChannel::SMS->value, 'recipient' => '111111', 'content' => 'Message 1', 'priority' => NotificationPriority::LOW->value],
                ['channel' => NotificationChannel::SMS->value, 'recipient' => '222222', 'content' => 'Message 2'],
                ['channel' => NotificationChannel::SMS->value, 'recipient' => '333333', 'content' => 'Message 3', 'priority' => NotificationPriority::LOW->value],
                ['channel' => NotificationChannel::SMS->value, 'recipient' => '444444', 'content' => 'Message 4', 'priority' => NotificationPriority::LOW->value],
                ['channel' => NotificationChannel::SMS->value, 'recipient' => '555555', 'content' => 'Message 5', 'priority' => NotificationPriority::LOW->value],
            ],
        ];

        $response = $this->postJson('/api/notifications/batch', $batchPayload);

        $response->assertStatus(201)
            ->assertJsonStructure(['batch_id', 'queued'])
            ->assertJson(['queued' => 5]);

        Queue::assertPushed(ProcessBatchNotificationJob::class);

        $batchId = $response->json('batch_id');

        $this->assertDatabaseCount('notifications', 5);
        $this->assertDatabaseHas('notifications', [
            'batch_id' => $batchId,
            'channel' => NotificationChannel::SMS->value,
            'recipient' => '111111',
            'content' => 'Message 1',
            'status' => NotificationStatus::PENDING->value,
            'priority' => NotificationPriority::LOW->value
        ]);
    }

    public function test_batch_method_returns_422_for_empty_batch(): void
    {
        $response = $this->postJson('/api/notifications/batch', [
            'notifications' => [],
            'priority' => NotificationPriority::NORMAL->value,
        ]);

        $response->assertStatus(422);
    }

    public function test_cancel_method_cancels_a_notification(): void
    {
        $notification = Notification::factory()->create([
            'status' => NotificationStatus::PENDING->value,
        ]);

        $response = $this->postJson("/api/notifications/{$notification->id}/cancel");

        $response->assertStatus(202)
            ->assertJson(['message' => 'Notification Cancelled']);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::CANCELLED->value,
        ]);
    }

    public function test_cancelBatch_method_cancels_batch_notifications(): void
    {
        $notifications = Notification::factory()->count(3)->create([
            'status' => NotificationStatus::PENDING->value,
        ]);

        $response = $this->postJson("/api/notifications/batch/{$notifications[0]->batch_id}/cancel");

        $response->assertStatus(202)
            ->assertJson(['message' => 'Batch Cancelled']);

        $this->assertDatabaseHas('notifications', [
            'id' => $notifications[0]->id,
            'status' => NotificationStatus::CANCELLED->value,
        ]);
    }

    public function test_index_method_fetches_notifications(): void
    {
        Notification::factory()->count(3)->create();

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
            'data',
            'meta' => [
                'path',
                'per_page',
                'next_cursor',
                'prev_cursor',
            ]
        ]);
    }


    public function test_index_method_preserves_query_params_in_cursor_next_link(): void
    {
        Notification::factory()->count(25)->create([
            'status' => NotificationStatus::PENDING->value,
            'channel' => NotificationChannel::EMAIL->value,
        ]);

        $response = $this->getJson('/api/notifications?status=pending&channel=email&perPage=10');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('next_cursor', $data['meta']);

        $nextCursor = $data['meta']['next_cursor'];

        $nextResponse = $this->getJson(
            "/api/notifications?status=pending&channel=email&perPage=10&cursor={$nextCursor}"
        );

        $nextResponse->assertStatus(200);

        $nextData = $nextResponse->json();

        foreach ($nextData['data'] as $item) {
            $this->assertEquals(NotificationStatus::PENDING->value, $item['status']);
            $this->assertEquals(NotificationChannel::EMAIL->value, $item['channel']);
        }
    }

    public function test_cursor_next_link_contains_query_parameters(): void
    {
        Notification::factory()->count(15)->create([
            'status' => NotificationStatus::PENDING->value,
        ]);

        $response = $this->getJson('/api/notifications?status=pending&perPage=5');

        $response->assertStatus(200);

        $payload = $response->json();

        $nextCursor = $payload['meta']['next_cursor'];

        $this->assertNotNull($nextCursor);

        if (isset($payload['links']['next'])) {
            $nextLink = $payload['links']['next'];

            $this->assertStringContainsString('status=pending', $nextLink);
            $this->assertStringContainsString('perPage=5', $nextLink);
        }
    }

    public function test_show_method_fetches_single_notification(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'content']
            ]);
    }

    public function test_show_method_returns_422_for_invalid_uuid(): void
    {
        $invalidId = 'not-a-uuid';

        $response = $this->getJson("/api/notifications/{$invalidId}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_showByBatch_method_fetches_batch_notifications(): void
    {
        $notifications = Notification::factory()->count(3)->create();

        $response = $this->getJson("/api/notifications/batch/{$notifications[0]->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'path',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                ]
            ]);
    }

    public function test_showByBatch_method_returns_422_for_invalid_uuid(): void
    {
        $invalidBatchId = 'invalid-batch';

        $response = $this->getJson("/api/notifications/batch/{$invalidBatchId}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['batchId']);
    }
}
