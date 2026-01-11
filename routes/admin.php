<?php

declare(strict_types=1);

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\FailedJobsList;
use App\Livewire\Admin\OrdersList;
use App\Livewire\Admin\PlansList;
use App\Livewire\Admin\ProvisioningLogsList;
use App\Livewire\Admin\SubscriptionsList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are protected
| by the 'auth', 'verified', and 'admin' middleware. Only admin users
| can access these routes.
|
*/

Route::middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::get('/dashboard', Dashboard::class)->name('admin.dashboard');

    // Analytics route
    Route::get('/analytics', Analytics::class)->name('admin.analytics');

    // Subscriptions management routes
    Route::get('/subscriptions', SubscriptionsList::class)->name('admin.subscriptions.index');

    // Orders management routes
    Route::get('/orders', OrdersList::class)->name('admin.orders.index');

    // Failed jobs management routes
    Route::get('/failed-jobs', FailedJobsList::class)->name('admin.failed-jobs.index');

    // Provisioning logs routes
    Route::get('/provisioning-logs', ProvisioningLogsList::class)->name('admin.provisioning-logs.index');

    // Plans management routes
    Route::get('/plans', PlansList::class)->name('admin.plans.index');
});
