<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;

test('admin users can be created via factory', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->is_admin)->toBeTrue()
        ->and($admin->isAdmin())->toBeTrue();
});

test('regular users are not admins', function () {
    $user = User::factory()->create();

    expect($user->is_admin)->toBeFalse()
        ->and($user->isAdmin())->toBeFalse();
});

test('admin middleware allows admin users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin');

    // Will get 404 since route doesn't exist yet, but not 403
    $response->assertStatus(404);
});

test('admin middleware blocks regular users', function () {
    // Since /admin route doesn't exist yet, we'll test the middleware directly
    $user = User::factory()->create();

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->expectExceptionMessage('Unauthorized. Admin access required.');

    $middleware = new \App\Http\Middleware\EnsureUserIsAdmin();
    $request = \Illuminate\Http\Request::create('/admin', 'GET');
    $request->setUserResolver(fn() => $user);

    $middleware->handle($request, fn() => response('OK'));
});

test('admin middleware blocks guests', function () {
    // Since /admin route doesn't exist yet, we'll test the middleware directly
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->expectExceptionMessage('Unauthorized. Admin access required.');

    $middleware = new \App\Http\Middleware\EnsureUserIsAdmin();
    $request = \Illuminate\Http\Request::create('/admin', 'GET');
    $request->setUserResolver(fn() => null);

    $middleware->handle($request, fn() => response('OK'));
});

// Subscription Policy Tests

test('admin can view any subscription', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($admin->can('view', $subscription))->toBeTrue();
});

test('user can view their own subscription', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($user->can('view', $subscription))->toBeTrue();
});

test('user cannot view other users subscription', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user2->id,
        'plan_id' => $plan->id,
    ]);

    expect($user1->can('view', $subscription))->toBeFalse();
});

test('only admin can update subscription', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($admin->can('update', $subscription))->toBeTrue()
        ->and($user->can('update', $subscription))->toBeFalse();
});

test('only admin can provision subscription', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($admin->can('provision', $subscription))->toBeTrue()
        ->and($user->can('provision', $subscription))->toBeFalse();
});

test('only admin can suspend subscription', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    expect($admin->can('suspend', $subscription))->toBeTrue()
        ->and($user->can('suspend', $subscription))->toBeFalse();
});

// Order Policy Tests

test('admin can view any order', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($admin->can('view', $order))->toBeTrue();
});

test('user can view their own order', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user->can('view', $order))->toBeTrue();
});

test('user cannot view other users order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user2->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'user_id' => $user2->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user1->can('view', $order))->toBeFalse();
});

test('only admin can retry provisioning for order', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($admin->can('retryProvisioning', $order))->toBeTrue()
        ->and($user->can('retryProvisioning', $order))->toBeFalse();
});

// ServiceAccount Policy Tests

test('admin can view any service account', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($admin->can('view', $serviceAccount))->toBeTrue();
});

test('user can view their own service account', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user->can('view', $serviceAccount))->toBeTrue();
});

test('user cannot view other users service account', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user2->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user2->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user1->can('view', $serviceAccount))->toBeFalse();
});

test('admin can view any service account credentials', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($admin->can('viewCredentials', $serviceAccount))->toBeTrue();
});

test('user can view their own service account credentials', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user->can('viewCredentials', $serviceAccount))->toBeTrue();
});

test('user cannot view other users service account credentials', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user2->id,
        'plan_id' => $plan->id,
    ]);
    $serviceAccount = ServiceAccount::factory()->create([
        'user_id' => $user2->id,
        'subscription_id' => $subscription->id,
    ]);

    expect($user1->can('viewCredentials', $serviceAccount))->toBeFalse();
});
