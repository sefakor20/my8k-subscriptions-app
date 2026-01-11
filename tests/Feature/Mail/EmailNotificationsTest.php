<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Mail\AccountCredentialsReady;
use App\Mail\PaymentFailureReminder;
use App\Mail\ProvisioningFailed;
use App\Mail\SubscriptionExpiringSoon;
use App\Mail\WelcomeNewCustomer;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\artisan;

// AccountCredentialsReady Mailable Tests

test('account credentials ready email has correct subject', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
    ]);

    $mailable = new AccountCredentialsReady($serviceAccount);

    $envelope = $mailable->envelope();

    expect($envelope->subject)->toBe('Your IPTV Account is Ready!');
});

test('account credentials ready email contains service account data', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'subscription_id' => $subscription->id,
        'user_id' => $user->id,
        'username' => 'testuser123',
        'password' => 'testpass456',
        'server_url' => 'http://server1.my8k.com:8080',
    ]);

    $mailable = new AccountCredentialsReady($serviceAccount);

    $content = $mailable->content();

    expect($content->markdown)->toBe('emails.account-credentials-ready')
        ->and($content->with)->toHaveKey('serviceAccount')
        ->and($content->with)->toHaveKey('subscription')
        ->and($content->with)->toHaveKey('user')
        ->and($content->with['serviceAccount']->username)->toBe('testuser123')
        ->and($content->with['serviceAccount']->password)->toBe('testpass456');
});

test('account credentials ready email implements should queue', function () {
    $mailable = new AccountCredentialsReady(
        ServiceAccount::factory()->make(),
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

// ProvisioningFailed Mailable Tests

test('provisioning failed email has correct subject with order id', function () {
    $order = Order::factory()->create([
        'woocommerce_order_id' => 12345,
    ]);

    $mailable = new ProvisioningFailed(
        order: $order,
        errorMessage: 'API connection failed',
        errorCode: 'ERR_API_TIMEOUT',
    );

    $envelope = $mailable->envelope();

    expect($envelope->subject)->toBe('[URGENT] Provisioning Failed - Order #12345');
});

test('provisioning failed email contains error details', function () {
    $order = Order::factory()->create();

    $mailable = new ProvisioningFailed(
        order: $order,
        errorMessage: 'API connection failed',
        errorCode: 'ERR_API_TIMEOUT',
    );

    expect($mailable->errorMessage)->toBe('API connection failed')
        ->and($mailable->errorCode)->toBe('ERR_API_TIMEOUT')
        ->and($mailable->order->id)->toBe($order->id);
});

test('provisioning failed email implements should queue', function () {
    $mailable = new ProvisioningFailed(
        order: Order::factory()->make(),
        errorMessage: 'Test error',
        errorCode: 'ERR_TEST',
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

// SubscriptionExpiringSoon Mailable Tests

test('subscription expiring soon email has correct subject with days', function () {
    $subscription = Subscription::factory()->create();

    $mailable = new SubscriptionExpiringSoon(
        subscription: $subscription,
        daysUntilExpiry: 7,
    );

    $envelope = $mailable->envelope();

    expect($envelope->subject)->toBe('Your IPTV Subscription Expires in 7 Days');
});

test('subscription expiring soon email contains expiry days', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $mailable = new SubscriptionExpiringSoon(
        subscription: $subscription,
        daysUntilExpiry: 3,
    );

    $content = $mailable->content();

    expect($content->with)->toHaveKey('daysUntilExpiry')
        ->and($content->with['daysUntilExpiry'])->toBe(3)
        ->and($content->with['subscription']->id)->toBe($subscription->id);
});

test('subscription expiring soon email implements should queue', function () {
    $mailable = new SubscriptionExpiringSoon(
        subscription: Subscription::factory()->make(),
        daysUntilExpiry: 7,
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

// PaymentFailureReminder Mailable Tests

test('payment failure reminder email has correct subject', function () {
    $subscription = Subscription::factory()->create();

    $mailable = new PaymentFailureReminder(
        subscription: $subscription,
        paymentMethod: 'Visa **** 1234',
    );

    $envelope = $mailable->envelope();

    expect($envelope->subject)->toContain('Payment Failed')
        ->and($envelope->subject)->toContain('Action Required');
});

test('payment failure reminder email contains payment method', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $mailable = new PaymentFailureReminder(
        subscription: $subscription,
        paymentMethod: 'Mastercard **** 5678',
    );

    expect($mailable->paymentMethod)->toBe('Mastercard **** 5678')
        ->and($mailable->subscription->id)->toBe($subscription->id);
});

test('payment failure reminder email implements should queue', function () {
    $mailable = new PaymentFailureReminder(
        subscription: Subscription::factory()->make(),
        paymentMethod: 'Visa **** 1234',
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

// WelcomeNewCustomer Mailable Tests

test('welcome new customer email has correct subject', function () {
    $user = User::factory()->create();

    $mailable = new WelcomeNewCustomer(
        user: $user,
        passwordResetUrl: 'https://example.com/reset-password',
    );

    $envelope = $mailable->envelope();

    expect($envelope->subject)->toContain('Welcome');
});

test('welcome new customer email contains password reset url', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    $mailable = new WelcomeNewCustomer(
        user: $user,
        passwordResetUrl: 'https://example.com/reset-password?token=abc123',
    );

    $content = $mailable->content();

    expect($content->with)->toHaveKey('passwordResetUrl')
        ->and($content->with['passwordResetUrl'])->toBe('https://example.com/reset-password?token=abc123')
        ->and($content->with['user']->name)->toBe('John Doe');
});

test('welcome new customer email implements should queue', function () {
    $mailable = new WelcomeNewCustomer(
        user: User::factory()->make(),
        passwordResetUrl: 'https://example.com/reset',
    );

    expect($mailable)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

// Integration Tests - SendExpiryRemindersCommand

test('expiry reminders command finds subscriptions expiring in specified days', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create subscription expiring in 7 days (set to start of day to match whereDate)
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(7)->startOfDay(),
        'auto_renew' => false,
    ]);

    // Create subscription expiring in 3 days (should not be picked up)
    Subscription::factory()->create([
        'user_id' => User::factory()->create()->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(3)->startOfDay(),
        'auto_renew' => false,
    ]);

    Mail::fake();

    artisan('subscriptions:send-expiry-reminders --days=7')
        ->assertSuccessful();

    Mail::assertQueued(SubscriptionExpiringSoon::class, 1);
    Mail::assertQueued(SubscriptionExpiringSoon::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->daysUntilExpiry === 7;
    });
});

test('expiry reminders command skips subscriptions with auto renew enabled', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create subscription with auto-renew enabled
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(7)->startOfDay(),
        'auto_renew' => true,
    ]);

    Mail::fake();

    artisan('subscriptions:send-expiry-reminders --days=7')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

test('expiry reminders command dry run does not send emails', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(7)->startOfDay(),
        'auto_renew' => false,
    ]);

    Mail::fake();

    artisan('subscriptions:send-expiry-reminders --days=7 --dry-run')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

test('expiry reminders command only sends to active subscriptions', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create expired subscription
    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Expired,
        'expires_at' => now()->addDays(7)->startOfDay(),
        'auto_renew' => false,
    ]);

    // Create suspended subscription
    Subscription::factory()->create([
        'user_id' => User::factory()->create()->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Suspended,
        'expires_at' => now()->addDays(7)->startOfDay(),
        'auto_renew' => false,
    ]);

    Mail::fake();

    artisan('subscriptions:send-expiry-reminders --days=7')
        ->assertSuccessful();

    Mail::assertNothingSent();
});
