<?php

declare(strict_types=1);

use App\Livewire\Dashboard\SubscriptionDetail;
use App\Models\Plan;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

it('can toggle m3u qr code visibility', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->assertSet('showM3uQrCode', false)
        ->call('toggleM3uQrCode')
        ->assertSet('showM3uQrCode', true)
        ->call('toggleM3uQrCode')
        ->assertSet('showM3uQrCode', false);
});

it('can toggle epg qr code visibility', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->assertSet('showEpgQrCode', false)
        ->call('toggleEpgQrCode')
        ->assertSet('showEpgQrCode', true)
        ->call('toggleEpgQrCode')
        ->assertSet('showEpgQrCode', false);
});

it('shows m3u qr code when toggled on with unlocked credentials', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->call('toggleM3uQrCode')
        ->assertSee('Scan with your IPTV app to auto-configure');
});

it('shows epg qr code when toggled on with unlocked credentials', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->call('toggleEpgQrCode')
        ->assertSee('Scan with your IPTV app for EPG guide');
});

it('resets qr code state when closing modal', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->call('toggleM3uQrCode')
        ->call('toggleEpgQrCode')
        ->assertSet('showM3uQrCode', true)
        ->assertSet('showEpgQrCode', true)
        ->call('closeModal')
        ->assertSet('showM3uQrCode', false)
        ->assertSet('showEpgQrCode', false);
});

it('resets qr code state when reopening modal', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials')
        ->call('toggleM3uQrCode')
        ->assertSet('showM3uQrCode', true);

    // Reopen the modal
    $component->call('openModal', $subscription->id)
        ->assertSet('showM3uQrCode', false)
        ->assertSet('showEpgQrCode', false);
});

it('generates valid svg for m3u qr code', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials');

    $m3uQrSvg = $component->get('m3uQrCodeSvg');

    expect($m3uQrSvg)->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('generates valid svg for epg qr code', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $serviceAccount = ServiceAccount::factory()
        ->forSubscription($subscription)
        ->active()
        ->create();

    $subscription->update(['service_account_id' => $serviceAccount->id]);

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id)
        ->call('unlockCredentials');

    $epgQrSvg = $component->get('epgQrCodeSvg');

    expect($epgQrSvg)->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('returns null for m3u qr code when no service account', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->pending()
        ->create();

    $component = Livewire::actingAs($user)
        ->test(SubscriptionDetail::class)
        ->call('openModal', $subscription->id);

    $m3uQrSvg = $component->get('m3uQrCodeSvg');

    expect($m3uQrSvg)->toBeNull();
});
