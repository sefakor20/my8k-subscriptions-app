<?php

declare(strict_types=1);

use App\Enums\NotificationCategory;
use App\Enums\NotificationLogStatus;
use App\Mail\InvoiceGenerated;
use App\Mail\PlanChangeConfirmed;
use App\Mail\SubscriptionExpiringSoon;
use App\Mail\SubscriptionRenewed;
use App\Mail\SuspensionWarning;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\PlanChange;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('sends mail when user has not configured preferences (defaults to enabled)', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    $mailable = new SubscriptionRenewed($subscription, $order);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->status)->toBe(NotificationLogStatus::Sent)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->notification_type)->toBe(SubscriptionRenewed::class)
        ->and($log->category)->toBe(NotificationCategory::RenewalReminders);

    Mail::assertQueued(SubscriptionRenewed::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('blocks mail when user has disabled the category', function () {
    $user = User::factory()->create();

    // Disable renewal reminders for this user
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::RenewalReminders,
        'channel' => 'mail',
        'is_enabled' => false,
    ]);

    $subscription = Subscription::factory()->forUser($user)->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);
    $mailable = new SubscriptionRenewed($subscription, $order);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->status)->toBe(NotificationLogStatus::Blocked)
        ->and($log->sent_at)->toBeNull();

    Mail::assertNothingQueued();
});

it('always sends critical notifications regardless of preferences', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create();

    // Even if user tries to disable critical notifications via direct DB manipulation
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::Critical,
        'channel' => 'mail',
        'is_enabled' => false,
    ]);

    $mailable = new SuspensionWarning($subscription);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log->status)->toBe(NotificationLogStatus::Sent);

    Mail::assertQueued(SuspensionWarning::class);
});

it('queues mail with preference check and logging', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create();

    $mailable = new SubscriptionExpiringSoon($subscription, 7);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable, ['subscription_id' => $subscription->id]);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->status)->toBe(NotificationLogStatus::Sent)
        ->and($log->metadata)->toBe(['subscription_id' => $subscription->id]);

    Mail::assertQueued(SubscriptionExpiringSoon::class);
});

it('blocks queued mail when preference is disabled', function () {
    $user = User::factory()->create();

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::RenewalReminders,
        'channel' => 'mail',
        'is_enabled' => false,
    ]);

    $subscription = Subscription::factory()->forUser($user)->create();
    $mailable = new SubscriptionExpiringSoon($subscription, 7);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log->status)->toBe(NotificationLogStatus::Blocked);

    Mail::assertNothingQueued();
});

it('initializes default preferences for a new user', function () {
    $user = User::factory()->create();

    $service = app(NotificationService::class);
    $service->initializeUserPreferences($user);

    $configurableCategories = NotificationCategory::configurable();
    $preferences = $user->notificationPreferences;

    expect($preferences)->toHaveCount(count($configurableCategories));

    foreach ($configurableCategories as $category) {
        $preference = $preferences->firstWhere('category', $category);
        expect($preference)->not->toBeNull()
            ->and($preference->is_enabled)->toBeTrue()
            ->and($preference->channel)->toBe('mail');
    }
});

it('does not duplicate preferences when initializing multiple times', function () {
    $user = User::factory()->create();

    $service = app(NotificationService::class);
    $service->initializeUserPreferences($user);
    $service->initializeUserPreferences($user);

    $configurableCategories = NotificationCategory::configurable();

    expect($user->notificationPreferences()->count())->toBe(count($configurableCategories));
});

it('updates user preference', function () {
    $user = User::factory()->create();

    $service = app(NotificationService::class);

    // Create initial preference
    $preference = $service->updatePreference($user, NotificationCategory::Invoices, true);
    expect($preference->is_enabled)->toBeTrue();

    // Update to disable
    $preference = $service->updatePreference($user, NotificationCategory::Invoices, false);
    expect($preference->is_enabled)->toBeFalse();

    // Verify only one record exists
    expect(NotificationPreference::where('user_id', $user->id)
        ->where('category', NotificationCategory::Invoices)
        ->count())->toBe(1);
});

it('gets all user preferences including defaults for missing categories', function () {
    $user = User::factory()->create();

    // Only set one preference
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::Invoices,
        'is_enabled' => false,
    ]);

    $service = app(NotificationService::class);
    $preferences = $service->getUserPreferences($user);

    $configurableCategories = NotificationCategory::configurable();

    expect($preferences)->toHaveCount(count($configurableCategories));

    // Find the invoice preference - should be disabled
    $invoicePref = collect($preferences)->firstWhere('category', NotificationCategory::Invoices);
    expect($invoicePref['is_enabled'])->toBeFalse();

    // Other preferences should default to enabled
    $renewalPref = collect($preferences)->firstWhere('category', NotificationCategory::RenewalReminders);
    expect($renewalPref['is_enabled'])->toBeTrue();
});

it('maps mailable classes to correct categories', function () {
    $service = app(NotificationService::class);

    expect($service->getCategoryForMailable(SubscriptionRenewed::class))
        ->toBe(NotificationCategory::RenewalReminders)
        ->and($service->getCategoryForMailable(SubscriptionExpiringSoon::class))
        ->toBe(NotificationCategory::RenewalReminders)
        ->and($service->getCategoryForMailable(InvoiceGenerated::class))
        ->toBe(NotificationCategory::Invoices)
        ->and($service->getCategoryForMailable(PlanChangeConfirmed::class))
        ->toBe(NotificationCategory::PlanChanges)
        ->and($service->getCategoryForMailable(SuspensionWarning::class))
        ->toBe(NotificationCategory::Critical);
});

it('defaults unknown mailables to critical category', function () {
    $service = app(NotificationService::class);

    // An unmapped mailable class should default to Critical
    $category = $service->getCategoryForMailable('App\\Mail\\SomeUnknownMailable');

    expect($category)->toBe(NotificationCategory::Critical);
});

it('logs notification with metadata', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    $mailable = new SubscriptionRenewed($subscription, $order);
    $metadata = [
        'subscription_id' => $subscription->id,
        'renewal_date' => now()->toDateString(),
    ];

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable, $metadata);

    expect($log->metadata)->toBe($metadata);
});

it('records sent_at timestamp when notification is sent', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    $mailable = new SubscriptionRenewed($subscription, $order);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log->sent_at)->not->toBeNull()
        ->and($log->sent_at->isToday())->toBeTrue();
});

it('does not record sent_at when notification is blocked', function () {
    $user = User::factory()->create();

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::Invoices,
        'is_enabled' => false,
    ]);

    $invoice = Invoice::factory()->create(['user_id' => $user->id]);
    $mailable = new InvoiceGenerated($invoice);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable);

    expect($log->sent_at)->toBeNull();
});

it('correctly determines if user has notification enabled', function () {
    $user = User::factory()->create();

    // No preference set - should default to enabled
    expect($user->hasNotificationEnabled(NotificationCategory::Invoices))->toBeTrue();

    // Create disabled preference
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::Invoices,
        'is_enabled' => false,
    ]);

    expect($user->hasNotificationEnabled(NotificationCategory::Invoices))->toBeFalse();

    // Critical category should always return true
    expect($user->hasNotificationEnabled(NotificationCategory::Critical))->toBeTrue();
});

it('sends invoice notification when preference is enabled', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $mailable = new InvoiceGenerated($invoice);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable, ['invoice_id' => $invoice->id]);

    expect($log->status)->toBe(NotificationLogStatus::Sent)
        ->and($log->category)->toBe(NotificationCategory::Invoices);

    Mail::assertQueued(InvoiceGenerated::class);
});

it('sends plan change notification when preference is enabled', function () {
    $user = User::factory()->create();
    $planChange = PlanChange::factory()->create(['user_id' => $user->id]);

    $mailable = new PlanChangeConfirmed($planChange);

    $service = app(NotificationService::class);
    $log = $service->queueMail($user, $mailable, ['plan_change_id' => $planChange->id]);

    expect($log->status)->toBe(NotificationLogStatus::Sent)
        ->and($log->category)->toBe(NotificationCategory::PlanChanges);

    Mail::assertQueued(PlanChangeConfirmed::class);
});
