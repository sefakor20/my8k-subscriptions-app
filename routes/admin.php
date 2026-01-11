<?php

declare(strict_types=1);

use App\Livewire\Admin\Dashboard;
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

    // Subscriptions management routes (to be implemented)
    // Route::get('/subscriptions', SubscriptionsList::class)->name('admin.subscriptions.index');

    // Orders management routes (to be implemented)
    // Route::get('/orders', OrdersList::class)->name('admin.orders.index');

    // Failed jobs management routes (to be implemented)
    // Route::get('/failed-jobs', FailedJobsList::class)->name('admin.failed-jobs.index');

    // Provisioning logs routes (to be implemented)
    // Route::get('/provisioning-logs', ProvisioningLogsList::class)->name('admin.provisioning-logs.index');

    // Plans management routes (to be implemented)
    // Route::get('/plans', PlansList::class)->name('admin.plans.index');
});
