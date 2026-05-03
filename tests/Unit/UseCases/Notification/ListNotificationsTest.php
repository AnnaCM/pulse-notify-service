<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\UseCases\Notification\ListNotifications;
use App\DTOs\Notification\ListNotificationsDTO;
use App\Support\Enums\NotificationChannel;
use App\Support\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_method_returns_all_notifications_when_no_filters_applied(): void
    {
        Notification::factory()->count(5)->create();

        $dto = new ListNotificationsDTO(
            status: null,
            channel: null,
            from: null,
            to: null,
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertCount(5, $result->items());
    }

    public function test_execute_method_applies_multiple_filters_together(): void
    {
        $old = Notification::factory()->create([
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $new = Notification::factory()->create([
            'created_at' => Carbon::now(),
        ]);

        Notification::factory()->create([
            'status' => NotificationStatus::SENT->value,
            'channel' => NotificationChannel::EMAIL->value,
        ]);

        Notification::factory()->create([
            'status' => NotificationStatus::SENT->value,
            'channel' => NotificationChannel::SMS->value,
        ]);

        $dto = new ListNotificationsDTO(
            status: NotificationStatus::SENT->value,
            channel: NotificationChannel::EMAIL->value,
            from: Carbon::now()->subDay(),
            to: Carbon::now()->addDay(),
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertCount(1, $result->items());
    }

    public function test_execute_method_filters_by_status(): void
    {
        Notification::factory()->create([
            'status' => NotificationStatus::SENT->value,
        ]);

        Notification::factory()->create([
            'status' => NotificationStatus::FAILED->value,
        ]);

        $dto = new ListNotificationsDTO(
            status: NotificationStatus::SENT->value,
            channel: null,
            from: null,
            to: null,
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertCount(1, $result->items());

        $this->assertEquals(
            NotificationStatus::SENT->value,
            $result->items()[0]->status
        );
    }

    public function test_execute_method_filters_by_channel(): void
    {
        Notification::factory()->create(['channel' => NotificationChannel::EMAIL->value]);
        Notification::factory()->create(['channel' => NotificationChannel::SMS->value]);

        $dto = new ListNotificationsDTO(
            status: null,
            channel: NotificationChannel::EMAIL->value,
            from: null,
            to: null,
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertCount(1, $result->items());

        $this->assertEquals(NotificationChannel::EMAIL->value, $result->items()[0]->channel);
    }

    public function test_execute_method_filters_by_date_range(): void
    {
        $old = Notification::factory()->create([
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $new = Notification::factory()->create([
            'created_at' => Carbon::now(),
        ]);

        $dto = new ListNotificationsDTO(
            status: null,
            channel: null,
            from: Carbon::now()->subDay(),
            to: Carbon::now()->addDay(),
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertCount(1, $result->items());
        $this->assertEquals($new->id, $result->items()[0]->id);
    }

    public function test_execute_method_respects_pagination_limit(): void
    {
        Notification::factory()->count(30)->create();

        $dto = new ListNotificationsDTO(
            status: null,
            channel: null,
            from: null,
            to: null,
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertLessThanOrEqual(10, count($result->items()));
    }

    public function test_execute_method_returns_cursor_paginator(): void
    {
        Notification::factory()->count(3)->create();

        $dto = new ListNotificationsDTO(
            status: null,
            channel: null,
            from: null,
            to: null,
            perPage: 10
        );

        $result = (new ListNotifications())->execute($dto);

        $this->assertInstanceOf(
            \Illuminate\Pagination\CursorPaginator::class,
            $result
        );
    }
}
