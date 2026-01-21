<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\VerifyPaystackWebhook;
use App\Http\Middleware\VerifyStripeWebhook;
use App\Http\Middleware\VerifyWooCommerceWebhook;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web'])
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'verify.woocommerce.webhook' => VerifyWooCommerceWebhook::class,
            'verify.paystack.webhook' => VerifyPaystackWebhook::class,
            'verify.stripe.webhook' => VerifyStripeWebhook::class,
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

        // Send expiry reminder emails at different intervals
        $schedule->command('subscriptions:send-expiry-reminders --days=7')
            ->daily()
            ->at('09:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('subscriptions:send-expiry-reminders --days=3')
            ->daily()
            ->at('09:30')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('subscriptions:send-expiry-reminders --days=1')
            ->daily()
            ->at('10:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Check reseller credit balance and send alerts if needed
        $schedule->command('credits:check-balance')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Monitor system health and send alerts for issues
        $schedule->command('system:monitor-health')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
