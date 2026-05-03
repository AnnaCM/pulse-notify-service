<?php

use App\Exceptions\BatchNotificationNotFoundException;
use App\Exceptions\CannotCancelNotificationException;
use App\Exceptions\NoPendingNotificationsException;
use App\Exceptions\NotificationNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {

            return match (true) {

                $e instanceof NotificationNotFoundException,
                $e instanceof BatchNotificationNotFoundException =>
                    response()->json(['message' => $e->getMessage()], 404),

                $e instanceof CannotCancelNotificationException,
                $e instanceof NoPendingNotificationsException =>
                    response()->json(['message' => $e->getMessage()], 400),

                default => null,
            };
        });
    })->create();
