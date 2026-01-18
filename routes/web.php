<?php

declare(strict_types=1);

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/orders', \App\Livewire\Dashboard\MyOrders::class)->name('orders.index');

    // Support Tickets
    Route::get('/support/my-tickets', \App\Livewire\Customer\MyTickets::class)->name('support.my-tickets');
});

// Checkout Routes
Route::middleware(['auth'])->prefix('checkout')->name('checkout.')->group(function (): void {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::get('/plan/{plan}', [CheckoutController::class, 'selectGateway'])->name('gateway');
    Route::post('/initiate', [CheckoutController::class, 'initiate'])->name('initiate');
    Route::get('/callback/{gateway}', [CheckoutController::class, 'callback'])->name('callback');
    Route::get('/success', [CheckoutController::class, 'success'])->name('success');
    Route::get('/cancel', [CheckoutController::class, 'cancel'])->name('cancel');
    Route::get('/verify/{reference}', [CheckoutController::class, 'verify'])->name('verify');
});

require __DIR__ . '/settings.php';
