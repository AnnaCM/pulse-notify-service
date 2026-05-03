<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Notification\ChannelRouter;
use App\Services\Notification\Contracts\NotificationProviderInterface;
use App\Models\Notification;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ChannelRouterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_send_method_calls_provider_and_returns_response(): void
    {
        $notification = new Notification([
            'recipient' => 'test@example.com',
            'content' => 'Hello',
            'channel' => 'email',
        ]);
        $key = 'key';
        $providerResponse = [
            'status' => 'accepted',
            'messageId' => '123',
            'timestamp' => 'ISO8601',
        ];

        $provider = Mockery::mock(NotificationProviderInterface::class);
        $provider->shouldReceive('send')
            ->once()
            ->with($notification, $key)
            ->andReturn($providerResponse);

        $router = new ChannelRouter($provider);

        $result = $router->send($notification, $key);

        $this->assertEquals($providerResponse['status'], $result['status']);
        $this->assertEquals($providerResponse['messageId'], $result['messageId']);
        $this->assertEquals($providerResponse['timestamp'], $result['timestamp']);
    }
}
