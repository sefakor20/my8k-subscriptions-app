<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\VerifyWooCommerceWebhook;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'verify.woocommerce.webhook' => VerifyWooCommerceWebhook::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Expire subscriptions that have passed their expiration date
        $schedule->command('subscriptions:expire')
            ->daily()
            ->at('01:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Reconcile WooCommerce orders to catch missed webhooks
        $schedule->command('woocommerce:reconcile-orders')
            ->daily()
            ->at('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old provisioning logs (runs weekly on Sunday)
        $schedule->command('logs:cleanup')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
