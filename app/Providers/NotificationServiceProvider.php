<?php

namespace App\Providers;

use App\Services\Notification\ChannelRouter;
use App\Services\Notification\Contracts\NotificationProviderInterface;
use App\Services\Notification\Contracts\NotificationRateLimiterInterface;
use App\Services\Notification\Drivers\WebhookSite as WebhookSiteDriver;
use App\Services\Notification\NotificationRateLimiter;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelRouter::class);

        $this->app->bind(
            NotificationProviderInterface::class,
            WebhookSiteDriver::class
        );

        $this->app->bind(
            NotificationRateLimiterInterface::class,
            NotificationRateLimiter::class
        );
    }
}
