<?php

declare(strict_types=1);

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\CohortAnalysis;
use App\Livewire\Admin\CouponAnalytics;
use App\Livewire\Admin\CouponsList;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\FailedJobsList;
use App\Livewire\Admin\InvoicesList;
use App\Livewire\Admin\NotificationLogsList;
use App\Livewire\Admin\OrdersList;
use App\Livewire\Admin\PlanChangesList;
use App\Livewire\Admin\PlansList;
use App\Livewire\Admin\ProvisioningLogsList;
use App\Livewire\Admin\ResellerCreditsManagement;
use App\Livewire\Admin\StreamingAppsList;
use App\Livewire\Admin\SubscriptionsList;
use App\Livewire\Admin\SupportTicketsList;
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

    // Analytics routes
    Route::get('/analytics', Analytics::class)->name('admin.analytics');
    Route::get('/analytics/cohorts', CohortAnalysis::class)->name('admin.analytics.cohorts');

    // Reseller credits management route
    Route::get('/credits', ResellerCreditsManagement::class)->name('admin.credits');

    // Subscriptions management routes
    Route::get('/subscriptions', SubscriptionsList::class)->name('admin.subscriptions.index');

    // Plan changes management routes
    Route::get('/plan-changes', PlanChangesList::class)->name('admin.plan-changes.index');

    // Orders management routes
    Route::get('/orders', OrdersList::class)->name('admin.orders.index');

    // Invoices management routes
    Route::get('/invoices', InvoicesList::class)->name('admin.invoices.index');

    // Failed jobs management routes
    Route::get('/failed-jobs', FailedJobsList::class)->name('admin.failed-jobs.index');

    // Provisioning logs routes
    Route::get('/provisioning-logs', ProvisioningLogsList::class)->name('admin.provisioning-logs.index');

    // Plans management routes
    Route::get('/plans', PlansList::class)->name('admin.plans.index');

    // Support tickets routes
    Route::get('/support/tickets', SupportTicketsList::class)->name('admin.support.tickets');

    // Notification logs routes
    Route::get('/notification-logs', NotificationLogsList::class)->name('admin.notification-logs.index');

    // Coupons management routes
    Route::get('/coupons', CouponsList::class)->name('admin.coupons.index');
    Route::get('/coupons/analytics', CouponAnalytics::class)->name('admin.coupons.analytics');

    // Streaming apps management routes
    Route::get('/streaming-apps', StreamingAppsList::class)->name('admin.streaming-apps.index');
});
