<?php

declare(strict_types=1);

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Livewire\Customer\StreamingApps;
use App\Models\StreamingApp;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can access streaming apps page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('Streaming Apps');
});

test('guest is redirected to login when accessing streaming apps', function () {
    $response = $this->get('/apps');

    $response->assertRedirect(route('login'));
});

test('streaming apps page displays active apps', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->create([
        'name' => 'Smart STB Player',
        'platform' => StreamingAppPlatform::Android,
        'type' => StreamingAppType::M3U,
    ]);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('Smart STB Player');
    $response->assertSee('Android');
    $response->assertSee('M3U');
});

test('inactive apps are not displayed', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->create(['name' => 'Active App']);
    StreamingApp::factory()->inactive()->create(['name' => 'Inactive App']);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('Active App');
    $response->assertDontSee('Inactive App');
});

test('recommended apps section displays recommended apps', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->recommended()->create(['name' => 'Recommended Player']);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('Recommended Apps');
    $response->assertSee('Recommended Player');
});

test('empty state displays when no apps exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('No apps available');
});

test('can filter apps by platform', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->android()->create(['name' => 'Android App']);
    StreamingApp::factory()->active()->windows()->create(['name' => 'Windows App']);

    Livewire::actingAs($user)
        ->test(StreamingApps::class)
        ->set('platformFilter', 'android')
        ->assertSee('Android App')
        ->assertDontSee('Windows App');
});

test('can filter apps by type', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->m3u()->create(['name' => 'M3U App']);
    StreamingApp::factory()->active()->mag()->create(['name' => 'MAG App']);

    Livewire::actingAs($user)
        ->test(StreamingApps::class)
        ->set('typeFilter', 'm3u')
        ->assertSee('M3U App')
        ->assertDontSee('MAG App');
});

test('can reset filters', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->android()->create(['name' => 'Android App']);
    StreamingApp::factory()->active()->windows()->create(['name' => 'Windows App']);

    Livewire::actingAs($user)
        ->test(StreamingApps::class)
        ->set('platformFilter', 'android')
        ->call('resetFilters')
        ->assertSee('Android App')
        ->assertSee('Windows App');
});

test('download button links to correct url', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->create([
        'name' => 'Test App',
        'download_url' => 'https://example.com/app.apk',
    ]);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('https://example.com/app.apk');
});

test('downloader code is displayed when present', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->withDownloaderCode()->create([
        'name' => 'Test App',
        'downloader_code' => '123456',
    ]);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('123456');
});

test('short url is displayed when present', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->create([
        'name' => 'Test App',
        'short_url' => '2u.pw/app',
    ]);

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('2u.pw/app');
});

test('help section is displayed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/apps');

    $response->assertSuccessful();
    $response->assertSee('How to use these apps');
});

test('apps are ordered by sort order', function () {
    $user = User::factory()->create();

    StreamingApp::factory()->active()->create(['name' => 'App C', 'sort_order' => 3]);
    StreamingApp::factory()->active()->create(['name' => 'App A', 'sort_order' => 1]);
    StreamingApp::factory()->active()->create(['name' => 'App B', 'sort_order' => 2]);

    $component = Livewire::actingAs($user)->test(StreamingApps::class);

    $apps = $component->instance()->apps;

    expect($apps->pluck('name')->toArray())->toBe(['App A', 'App B', 'App C']);
});
