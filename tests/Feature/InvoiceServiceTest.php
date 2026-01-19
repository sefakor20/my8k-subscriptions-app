<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
});

it('creates an invoice for an order', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'name' => 'Premium Plan',
        'price' => 99.99,
        'currency' => 'USD',
        'duration_days' => 30,
    ]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create(['status' => SubscriptionStatus::Active]);

    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'status' => OrderStatus::Provisioned,
        'amount' => 99.99,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceForOrder($order);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->order_id)->toBe($order->id)
        ->and($invoice->user_id)->toBe($user->id)
        ->and((float) $invoice->subtotal)->toBe(99.99)
        ->and((float) $invoice->total)->toBe(99.99)
        ->and($invoice->currency)->toBe('USD')
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->invoice_number)->toStartWith('INV-' . now()->year . '-')
        ->and($invoice->line_items)->toBeArray()
        ->and($invoice->line_items[0]['description'])->toBe('Premium Plan');
});

it('generates a unique invoice number', function () {
    $invoiceNumber1 = Invoice::generateInvoiceNumber();

    // Create an invoice with that number to advance the sequence
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'invoice_number' => $invoiceNumber1,
    ]);

    $invoiceNumber2 = Invoice::generateInvoiceNumber();

    expect($invoiceNumber1)->toMatch('/^INV-\d{4}-\d{5}$/')
        ->and($invoiceNumber2)->toMatch('/^INV-\d{4}-\d{5}$/')
        ->and($invoiceNumber1)->not->toBe($invoiceNumber2);
});

it('generates invoice numbers sequentially', function () {
    $user = User::factory()->create();
    $order1 = Order::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order1->id,
        'invoice_number' => 'INV-' . now()->year . '-00001',
    ]);

    $nextNumber = Invoice::generateInvoiceNumber();

    expect($nextNumber)->toBe('INV-' . now()->year . '-00002');
});

it('generates PDF content for an invoice', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
    ]);

    $service = app(InvoiceService::class);
    $pdfContent = $service->getPdfContent($invoice);

    expect($pdfContent)->toBeString()
        ->and(mb_strlen($pdfContent))->toBeGreaterThan(0);
});

it('stores PDF after generation', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
    ]);

    $service = app(InvoiceService::class);
    $path = $service->generatePdf($invoice);

    $invoice->refresh();

    expect($path)->toBeString()
        ->and($invoice->pdf_path)->toBe($path)
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

it('sends invoice email after processing', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'paid_at' => now(),
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->processOrderInvoice($order);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->pdf_path)->not->toBeNull();

    Mail::assertQueued(InvoiceGenerated::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('does not create duplicate invoices for the same order (idempotency)', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'paid_at' => now(),
    ]);

    $service = app(InvoiceService::class);

    // First call - creates invoice
    $invoice1 = $service->processOrderInvoice($order);

    // Reload order to get fresh invoice relation
    $order->refresh();

    // Second call - should return existing invoice
    $invoice2 = $service->processOrderInvoice($order);

    expect($invoice1->id)->toBe($invoice2->id)
        ->and(Invoice::where('order_id', $order->id)->count())->toBe(1);
});

it('voids an invoice successfully', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => InvoiceStatus::Paid,
    ]);

    $service = app(InvoiceService::class);
    $result = $service->voidInvoice($invoice, 'Customer requested refund');

    $invoice->refresh();

    expect($result)->toBeTrue()
        ->and($invoice->status)->toBe(InvoiceStatus::Void)
        ->and($invoice->void_reason)->toBe('Customer requested refund')
        ->and($invoice->voided_at)->not->toBeNull();
});

it('cannot void an already voided invoice', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'status' => InvoiceStatus::Void,
        'voided_at' => now(),
    ]);

    $service = app(InvoiceService::class);
    $result = $service->voidInvoice($invoice, 'Another reason');

    expect($result)->toBeFalse()
        ->and($invoice->void_reason)->toBeNull();
});

it('formats total correctly using Number currency', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'total' => 1234.56,
        'currency' => 'USD',
    ]);

    // Number::currency() returns locale-formatted currency string
    expect($invoice->formattedTotal())->toContain('1,234.56');
});

it('calculates tax correctly when tax rate is set', function () {
    config(['invoice.defaults.tax_rate' => 10]);

    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'price' => 100.00,
        'currency' => 'USD',
    ]);
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'amount' => 100.00,
        'currency' => 'USD',
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceForOrder($order);

    expect((float) $invoice->subtotal)->toBe(100.00)
        ->and((float) $invoice->tax_amount)->toBe(10.00)
        ->and((float) $invoice->total)->toBe(110.00);

    config(['invoice.defaults.tax_rate' => 0]);
});

it('includes company details in invoice', function () {
    config([
        'invoice.company.name' => 'Test Company',
        'invoice.company.email' => 'test@example.com',
    ]);

    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceForOrder($order);

    expect($invoice->company_details)->toBeArray()
        ->and($invoice->company_details['name'])->toBe('Test Company')
        ->and($invoice->company_details['email'])->toBe('test@example.com');
});

it('includes customer details in invoice', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceForOrder($order);

    expect($invoice->customer_details)->toBeArray()
        ->and($invoice->customer_details['name'])->toBe('John Doe')
        ->and($invoice->customer_details['email'])->toBe('john@example.com');
});

it('returns correct PDF filename', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'invoice_number' => 'INV-2026-00001',
    ]);

    expect($invoice->getPdfFilename())->toBe('invoice-INV-2026-00001.pdf');
});

it('scopes paid invoices correctly', function () {
    $user = User::factory()->create();

    Invoice::factory()->count(3)->create([
        'user_id' => $user->id,
        'status' => InvoiceStatus::Paid,
    ]);

    Invoice::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => InvoiceStatus::Issued,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'status' => InvoiceStatus::Void,
    ]);

    expect(Invoice::paid()->count())->toBe(3)
        ->and(Invoice::issued()->count())->toBe(2);
});

it('determines if invoice can be voided', function () {
    $paidInvoice = Invoice::factory()->make(['status' => InvoiceStatus::Paid]);
    $issuedInvoice = Invoice::factory()->make(['status' => InvoiceStatus::Issued]);
    $voidInvoice = Invoice::factory()->make(['status' => InvoiceStatus::Void]);
    $draftInvoice = Invoice::factory()->make(['status' => InvoiceStatus::Draft]);

    expect($paidInvoice->canBeVoided())->toBeTrue()
        ->and($issuedInvoice->canBeVoided())->toBeTrue()
        ->and($voidInvoice->canBeVoided())->toBeFalse()
        ->and($draftInvoice->canBeVoided())->toBeTrue();
});

it('regenerates PDF successfully', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->create();
    $order = Order::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
    ]);

    $service = app(InvoiceService::class);

    // Generate first PDF
    $oldPath = $service->generatePdf($invoice);
    expect(Storage::disk('local')->exists($oldPath))->toBeTrue();

    // Regenerate PDF - new path
    $newPath = $service->regeneratePdf($invoice);

    // The new path should exist and have content
    expect(Storage::disk('local')->exists($newPath))->toBeTrue();
});
