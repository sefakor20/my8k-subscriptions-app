<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

// Process subscription renewals every hour
Schedule::command('subscriptions:renew --limit=50')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/subscription-renewals.log'));

// Send suspension warnings daily at 9:00 AM
Schedule::command('subscriptions:send-suspension-warnings --days=2')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/suspension-warnings.log'));

// Suspend subscriptions with expired grace periods daily at 10:00 AM
Schedule::command('subscriptions:suspend-failed-payments --limit=100')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/failed-payment-suspensions.log'));

// Reconcile provisioned subscriptions with pending status daily at 3:00 AM
Schedule::command('subscriptions:reconcile-provisioned-status --limit=20')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/subscription-reconciliation.log'));
