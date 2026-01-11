<?php

declare(strict_types=1);

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Livewire\Admin\ProvisioningLogDetailModal;
use App\Models\Order;
use App\Models\Plan;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('modal opens when event is dispatched', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create();

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSet('show', true)
        ->assertSet('logId', $log->id);
});

test('modal loads log data correctly', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'customer@example.com']);
    $plan = Plan::factory()->create(['name' => 'Premium Plan']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $log = ProvisioningLog::factory()->create([
        'subscription_id' => $subscription->id,
        'action' => ProvisioningAction::Create,
        'status' => ProvisioningStatus::Success,
        'attempt_number' => 1,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('Premium Plan');
});

test('modal displays error information for failed logs', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Failed,
        'error_message' => 'Connection timeout error',
        'error_code' => 'ERR_CONNECTION_TIMEOUT',
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('Connection timeout error')
        ->assertSee('ERR_CONNECTION_TIMEOUT');
});

test('modal displays service account information', function () {
    $admin = User::factory()->admin()->create();
    $serviceAccount = ServiceAccount::factory()->create([
        'username' => 'test_account_123',
        'server_url' => 'http://panel.example.com',
    ]);

    $log = ProvisioningLog::factory()->create([
        'service_account_id' => $serviceAccount->id,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('test_account_123');
});

test('modal displays order information', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    $log = ProvisioningLog::factory()->create([
        'order_id' => $order->id,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee($order->id);
});

test('modal displays my8k api request data', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'my8k_request' => json_encode([
            'username' => 'test_user',
            'plan_code' => 'PREMIUM',
        ]),
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('test_user')
        ->assertSee('PREMIUM');
});

test('modal displays my8k api response data', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'my8k_response' => json_encode([
            'status' => 'success',
            'account_id' => '12345',
        ]),
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('success')
        ->assertSee('12345');
});

test('modal closes when close method is called', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create();

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSet('show', true)
        ->call('closeModal')
        ->assertSet('show', false);
});

test('modal displays attempt count', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'attempt_number' => 5,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('5');
});

test('modal handles log without error gracefully', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'status' => ProvisioningStatus::Success,
        'error_message' => null,
        'error_code' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSee('success')
        ->assertDontSee('Error Information');
});

test('modal handles log without service account', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'service_account_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSuccessful();
});

test('modal handles log without order', function () {
    $admin = User::factory()->admin()->create();
    $log = ProvisioningLog::factory()->create([
        'order_id' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ProvisioningLogDetailModal::class)
        ->dispatch('open-log-modal', logId: $log->id)
        ->assertSuccessful();
});
