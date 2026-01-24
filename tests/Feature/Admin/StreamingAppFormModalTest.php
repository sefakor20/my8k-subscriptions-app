<?php

declare(strict_types=1);

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Livewire\Admin\StreamingAppFormModal;
use App\Models\StreamingApp;
use App\Models\User;
use Livewire\Livewire;

test('modal opens in create mode', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->assertSet('show', true)
        ->assertSet('mode', 'create')
        ->assertSet('appId', null);
});

test('modal opens in edit mode with app data', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create([
        'name' => 'Test Player',
        'description' => 'A test player',
        'platform' => StreamingAppPlatform::Android,
        'type' => StreamingAppType::M3U,
        'version' => 'v1.0.0',
        'download_url' => 'https://example.com/app.apk',
        'downloader_code' => '123456',
        'short_url' => '2u.pw/test',
        'is_recommended' => true,
        'is_active' => true,
        'sort_order' => 5,
    ]);

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'edit', appId: $app->id)
        ->assertSet('show', true)
        ->assertSet('mode', 'edit')
        ->assertSet('appId', $app->id)
        ->assertSet('name', 'Test Player')
        ->assertSet('description', 'A test player')
        ->assertSet('platform', 'android')
        ->assertSet('type', 'm3u')
        ->assertSet('version', 'v1.0.0')
        ->assertSet('download_url', 'https://example.com/app.apk')
        ->assertSet('downloader_code', '123456')
        ->assertSet('short_url', '2u.pw/test')
        ->assertSet('is_recommended', true)
        ->assertSet('is_active', true)
        ->assertSet('sort_order', '5');
});

test('can create a new streaming app', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->set('name', 'New Player')
        ->set('description', 'A new streaming player')
        ->set('platform', 'android')
        ->set('type', 'm3u')
        ->set('version', 'v2.0.0')
        ->set('download_url', 'https://example.com/new-app.apk')
        ->set('downloader_code', '654321')
        ->set('short_url', '2u.pw/new')
        ->set('is_recommended', true)
        ->set('is_active', true)
        ->set('sort_order', '10')
        ->call('save')
        ->assertDispatched('streaming-app-saved');

    $this->assertDatabaseHas('streaming_apps', [
        'name' => 'New Player',
        'description' => 'A new streaming player',
        'platform' => 'android',
        'type' => 'm3u',
        'version' => 'v2.0.0',
        'download_url' => 'https://example.com/new-app.apk',
        'downloader_code' => '654321',
        'short_url' => '2u.pw/new',
        'is_recommended' => true,
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

test('can update an existing streaming app', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create([
        'name' => 'Old Name',
        'download_url' => 'https://example.com/old.apk',
    ]);

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'edit', appId: $app->id)
        ->set('name', 'Updated Name')
        ->set('download_url', 'https://example.com/updated.apk')
        ->call('save')
        ->assertDispatched('streaming-app-saved');

    expect($app->fresh())
        ->name->toBe('Updated Name')
        ->download_url->toBe('https://example.com/updated.apk');
});

test('name is required', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->set('name', '')
        ->set('download_url', 'https://example.com/app.apk')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('download url is required', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->set('name', 'Test App')
        ->set('download_url', '')
        ->call('save')
        ->assertHasErrors(['download_url']);
});

test('download url must be a valid url', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->set('name', 'Test App')
        ->set('download_url', 'not-a-valid-url')
        ->call('save')
        ->assertHasErrors(['download_url']);
});

test('modal closes after successful save', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'create')
        ->set('name', 'Test App')
        ->set('download_url', 'https://example.com/app.apk')
        ->call('save')
        ->assertSet('show', false);
});

test('form resets when closing modal', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create(['name' => 'Test App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class)
        ->dispatch('open-streaming-app-form-modal', mode: 'edit', appId: $app->id)
        ->assertSet('name', 'Test App')
        ->call('closeModal')
        ->assertSet('name', '')
        ->assertSet('appId', null);
});

test('platform enum values are available', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class);

    $platforms = $component->instance()->getPlatforms();

    expect($platforms)->toBeArray()
        ->and(collect($platforms)->pluck('value'))->toContain('android', 'windows', 'smart_tv');
});

test('type enum values are available', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(StreamingAppFormModal::class);

    $types = $component->instance()->getTypes();

    expect($types)->toBeArray()
        ->and(collect($types)->pluck('value'))->toContain('m3u', 'mag', 'enigma2');
});
