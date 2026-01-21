<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

// Health Check Routes
Route::prefix('health')->group(function (): void {
    Route::get('ping', [HealthCheckController::class, 'ping'])->name('health.ping');
    Route::get('/', [HealthCheckController::class, 'check'])->name('health.check');
    Route::get('detailed', [HealthCheckController::class, 'detailed'])
        ->middleware(['web', 'auth', 'admin'])
        ->name('health.detailed');
});

// WooCommerce Webhook Routes
Route::prefix('v1/webhooks/woocommerce')
    ->middleware(['verify.woocommerce.webhook'])
    ->controller(WooCommerceWebhookController::class)
    ->group(function (): void {
        Route::post('order-completed', 'orderCompleted')->name('webhooks.woocommerce.order-completed');
        Route::post('subscription-renewed', 'subscriptionRenewed')->name('webhooks.woocommerce.subscription-renewed');
        Route::post('subscription-cancelled', 'subscriptionCancelled')->name('webhooks.woocommerce.subscription-cancelled');
        Route::post('payment-failed', 'paymentFailed')->name('webhooks.woocommerce.payment-failed');
    });

// Paystack Webhook Routes
Route::prefix('v1/webhooks/paystack')
    ->middleware(['verify.paystack.webhook'])
    ->controller(PaystackWebhookController::class)
    ->group(function (): void {
        Route::post('/', 'handle')->name('webhooks.paystack');
    });

// Stripe Webhook Routes
Route::prefix('v1/webhooks/stripe')
    ->middleware(['verify.stripe.webhook'])
    ->controller(StripeWebhookController::class)
    ->group(function (): void {
        Route::post('/', 'handle')->name('webhooks.stripe');
    });
