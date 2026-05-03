<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\Services\Notification\Drivers\WebhookSite;
use App\Models\Notification;

class WebhookSiteTest extends TestCase
{
    public function test_send_method_sends_notification_successfully(): void
    {
        $url = 'https://example.com/webhook';
        Config::set('services.webhook.url', $url);

        Http::fake([
            $url => Http::response([
                'messageId' => '123-abc',
                'status' => 'accepted',
                'timestamps' => 'ISO8601',
            ], 202),
        ]);

        $notification = new Notification([
            'recipient' => 'test@example.com',
            'channel' => 'email',
            'content' => 'Hello world',
        ]);
        $correlationId = 'corr-123';

        $driver = new WebhookSite();

        $result = $driver->send($notification, $correlationId);

        $this->assertTrue($result['success']);
        $this->assertEquals('123-abc', $result['messageId']);
        $this->assertEquals('accepted', $result['raw']['status']);
        $this->assertEquals('ISO8601', $result['raw']['timestamps']);

        Http::assertSent(function ($request) use ($url, $notification, $correlationId) {
            return $request->url() === $url
                && $request['to'] === $notification->recipient
                && $request['channel'] === $notification->channel
                && $request['content'] === $notification->content
                && $request['correlation_id'] === $correlationId;
        });
    }

    public function test_send_method_returns_failure_on_non_202_response(): void
    {
        $url = 'https://example.com/webhook';
        Config::set('services.webhook.url', $url);

        Http::fake([
           $url => Http::response([
                'messageId' => 'fail-id',
            ], 500),
        ]);

        $notification = new Notification([
            'recipient' => 'test@example.com',
            'channel' => 'email',
            'content' => 'Hello world',
        ]);

        $driver = new WebhookSite();

        $result = $driver->send($notification, 'corr-123');

        $this->assertFalse($result['success']);
        $this->assertEquals('fail-id', $result['messageId']);
    }

    public function test_send_method_handles_missing_message_id(): void
    {
        $url = 'https://example.com/webhook';
        Config::set('services.webhook.url', $url);

        Http::fake([
            $url => Http::response([
                'status' => 'accepted',
            ], 202),
        ]);

        $notification = new Notification([
            'recipient' => 'test@example.com',
            'channel' => 'email',
            'content' => 'Hello world',
        ]);

        $driver = new WebhookSite();

        $result = $driver->send($notification, 'corr-123');

        $this->assertTrue($result['success']);
        $this->assertNull($result['messageId']);
    }

    public function test_send_method_throws_exception_if_url_not_configured(): void
    {
        Config::set('services.webhook.url', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Webhook URL not configured');

        $notification = new Notification([
            'recipient' => 'test@example.com',
            'channel' => 'email',
            'content' => 'Hello world',
        ]);

        $driver = new WebhookSite();

        $driver->send($notification, 'corr-123');
    }
}
