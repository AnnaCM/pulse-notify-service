<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\UseCases\Notification\GetBatchNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetBatchNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_returns_notifications_for_given_batch(): void
    {
        $batchId = (string) Str::uuid();

        Notification::factory()->count(3)->create([
            'batch_id' => $batchId,
        ]);

        Notification::factory()->count(2)->create([
            'batch_id' => (string) Str::uuid(),
        ]);

        $useCase = new GetBatchNotification();
        $result = $useCase->execute($batchId);

        $this->assertCount(3, $result->items());

        foreach ($result->items() as $item) {
            $this->assertEquals($batchId, $item->batch_id);
        }
    }

    public function test_execute_method_orders_by_created_at_and_id_desc(): void
    {
        $batchId = (string) Str::uuid();

        $now = Carbon::now();

        $notification1 = Notification::factory()->create([
            'batch_id' => $batchId,
            'created_at' => $now->copy()->subSeconds(10),
        ]);

        $notification2 = Notification::factory()->create([
            'batch_id' => $batchId,
            'created_at' => $now,
        ]);

        $notification3 = Notification::factory()->create([
            'batch_id' => $batchId,
            'created_at' => $now,
        ]);

        $useCase = new GetBatchNotification();
        $result = $useCase->execute($batchId);

        $items = collect($result->items());

        $this->assertEquals($notification3->created_at, $items[0]->created_at);
        $this->assertEquals($notification2->created_at, $items[1]->created_at);
        $this->assertEquals($notification1->created_at, $items[2]->created_at);
    }

    public function test_execute_method_respects_pagination_limit(): void
    {
        $batchId = (string) Str::uuid();

        Notification::factory()->count(25)->create([
            'batch_id' => $batchId,
        ]);

        $useCase = new GetBatchNotification();
        $result = $useCase->execute($batchId);

        $this->assertCount(GetBatchNotification::LIMIT, $result->items());
    }

    public function test_execute_method_returns_cursor_paginator(): void
    {
        $batchId = (string) Str::uuid();

        Notification::factory()->count(5)->create([
            'batch_id' => $batchId,
        ]);

        $useCase = new GetBatchNotification();
        $result = $useCase->execute($batchId);

        $this->assertInstanceOf(\Illuminate\Pagination\CursorPaginator::class, $result);
    }

    public function test_execute_method_returns_empty_when_no_notifications(): void
    {
        $batchId = (string) Str::uuid();

        $useCase = new GetBatchNotification();
        $result = $useCase->execute($batchId);

        $this->assertCount(0, $result->items());
    }
}
