<?php

declare(strict_types=1);

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Livewire\Admin\StreamingAppsList;
use App\Models\StreamingApp;
use App\Models\User;
use Livewire\Livewire;

test('admin can access streaming apps list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Streaming Apps');
});

test('non-admin user gets 403 on streaming apps list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/streaming-apps');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing streaming apps list', function () {
    $response = $this->get('/admin/streaming-apps');

    $response->assertRedirect(route('login'));
});

test('streaming apps list displays apps correctly', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->create([
        'name' => 'Smart STB Player',
        'platform' => StreamingAppPlatform::Android,
        'type' => StreamingAppType::M3U,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Smart STB Player');
    $response->assertSee('Android');
    $response->assertSee('M3U');
});

test('empty state displays when no apps exist', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('No streaming apps found');
});

test('add app button is visible', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Add App');
});

test('active apps show active badge', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->active()->create(['name' => 'Active App']);

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Active');
});

test('inactive apps show inactive badge', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->inactive()->create(['name' => 'Inactive App']);

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Inactive');
});

test('recommended apps show recommended badge', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->recommended()->create(['name' => 'Recommended App']);

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('Recommended');
});

test('can filter by platform', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->android()->create(['name' => 'Android App']);
    StreamingApp::factory()->windows()->create(['name' => 'Windows App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('platformFilter', 'android')
        ->assertSee('Android App')
        ->assertDontSee('Windows App');
});

test('can filter by type', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->m3u()->create(['name' => 'M3U App']);
    StreamingApp::factory()->mag()->create(['name' => 'MAG App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('typeFilter', 'm3u')
        ->assertSee('M3U App')
        ->assertDontSee('MAG App');
});

test('can filter active apps only', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->active()->create(['name' => 'Active App']);
    StreamingApp::factory()->inactive()->create(['name' => 'Inactive App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('activeFilter', true)
        ->assertSee('Active App')
        ->assertDontSee('Inactive App');
});

test('can filter inactive apps only', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->active()->create(['name' => 'Active App']);
    StreamingApp::factory()->inactive()->create(['name' => 'Inactive App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('activeFilter', false)
        ->assertSee('Inactive App')
        ->assertDontSee('Active App');
});

test('can search apps by name', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->create(['name' => 'Smart STB Player']);
    StreamingApp::factory()->create(['name' => 'Fast 8K Player']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('search', 'Smart')
        ->assertSee('Smart STB Player')
        ->assertDontSee('Fast 8K Player');
});

test('can reset filters', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->android()->create(['name' => 'Android App']);
    StreamingApp::factory()->windows()->create(['name' => 'Windows App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->set('platformFilter', 'android')
        ->call('resetFilters')
        ->assertSee('Android App')
        ->assertSee('Windows App');
});

test('can toggle app active status', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->active()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->call('toggleActive', $app->id);

    expect($app->fresh()->is_active)->toBeFalse();
});

test('can toggle app recommended status', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create(['is_recommended' => false]);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->call('toggleRecommended', $app->id);

    expect($app->fresh()->is_recommended)->toBeTrue();
});

test('can delete app', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create(['name' => 'Deletable App']);

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->call('deleteApp', $app->id);

    $this->assertDatabaseMissing('streaming_apps', ['id' => $app->id]);
});

test('edit app dispatches modal event', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->call('editApp', $app->id)
        ->assertDispatched('open-streaming-app-form-modal');
});

test('create app dispatches modal event', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StreamingAppsList::class)
        ->call('createApp')
        ->assertDispatched('open-streaming-app-form-modal');
});

test('apps show downloader code when present', function () {
    $admin = User::factory()->admin()->create();

    StreamingApp::factory()->withDownloaderCode()->create(['downloader_code' => '123456']);

    $response = $this->actingAs($admin)->get('/admin/streaming-apps');

    $response->assertSuccessful();
    $response->assertSee('123456');
});

test('component refreshes after app saved event', function () {
    $admin = User::factory()->admin()->create();
    $app = StreamingApp::factory()->create(['name' => 'Original Name']);

    $component = Livewire::actingAs($admin)
        ->test(StreamingAppsList::class);

    $app->update(['name' => 'Updated Name']);

    $component->dispatch('streaming-app-saved', message: 'App updated')
        ->assertSee('Updated Name');
});
