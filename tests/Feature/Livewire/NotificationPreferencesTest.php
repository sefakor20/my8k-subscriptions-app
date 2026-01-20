<?php

declare(strict_types=1);

use App\Enums\NotificationCategory;
use App\Livewire\Settings\Notifications;
use App\Models\NotificationPreference;
use App\Models\User;
use Livewire\Livewire;

test('notification preferences page is displayed', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/notifications')->assertOk();
});

test('notification preferences page shows all configurable categories', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Notifications::class);

    foreach (NotificationCategory::configurable() as $category) {
        $response->assertSee($category->label());
        $response->assertSee($category->description());
    }
});

test('notification preferences page shows critical category as always enabled', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Notifications::class);

    $response->assertSee(NotificationCategory::Critical->label());
    $response->assertSee(NotificationCategory::Critical->description());
});

test('user can toggle a notification preference off', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class);

    // Initially enabled by default
    expect($component->get('preferences')[NotificationCategory::Invoices->value] ?? true)->toBeTrue();

    // Toggle off
    $component->call('togglePreference', NotificationCategory::Invoices->value);

    expect($component->get('preferences')[NotificationCategory::Invoices->value])->toBeFalse();

    // Verify database was updated
    $preference = NotificationPreference::where('user_id', $user->id)
        ->where('category', NotificationCategory::Invoices)
        ->first();

    expect($preference)->not->toBeNull()
        ->and($preference->is_enabled)->toBeFalse();
});

test('user can toggle a notification preference on', function (): void {
    $user = User::factory()->create();

    // Pre-create a disabled preference
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::RenewalReminders,
        'is_enabled' => false,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class);

    // Should be disabled initially
    expect($component->get('preferences')[NotificationCategory::RenewalReminders->value])->toBeFalse();

    // Toggle on
    $component->call('togglePreference', NotificationCategory::RenewalReminders->value);

    expect($component->get('preferences')[NotificationCategory::RenewalReminders->value])->toBeTrue();

    // Verify database was updated
    $preference = NotificationPreference::where('user_id', $user->id)
        ->where('category', NotificationCategory::RenewalReminders)
        ->first();

    expect($preference->is_enabled)->toBeTrue();
});

test('toggling critical category has no effect', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class);

    // Attempt to toggle critical category
    $component->call('togglePreference', NotificationCategory::Critical->value);

    // Critical should not appear in user preferences (not configurable)
    // and no preference record should be created
    $preference = NotificationPreference::where('user_id', $user->id)
        ->where('category', NotificationCategory::Critical)
        ->first();

    expect($preference)->toBeNull();
});

test('preference update dispatches event', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class)
        ->call('togglePreference', NotificationCategory::Invoices->value)
        ->assertDispatched('preference-updated');
});

test('preferences are loaded from database on mount', function (): void {
    $user = User::factory()->create();

    // Create some preferences in the database
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::Invoices,
        'is_enabled' => false,
    ]);

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'category' => NotificationCategory::PlanChanges,
        'is_enabled' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class);

    expect($component->get('preferences')[NotificationCategory::Invoices->value])->toBeFalse()
        ->and($component->get('preferences')[NotificationCategory::PlanChanges->value])->toBeTrue();
});

test('unauthenticated users cannot access notification preferences', function (): void {
    $this->get('/settings/notifications')
        ->assertRedirect('/login');
});
