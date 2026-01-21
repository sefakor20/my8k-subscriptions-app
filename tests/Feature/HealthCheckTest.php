<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ping endpoint returns ok status', function () {
    $response = $this->getJson('/api/health/ping');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
        ])
        ->assertJsonStructure([
            'status',
            'timestamp',
        ]);
});

test('health check endpoint returns system status', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'checks' => [
                'database',
                'cache',
                'queue',
                'storage',
            ],
        ]);
});

test('detailed health check requires authentication', function () {
    $response = $this->get('/api/health/detailed');

    $response->assertRedirect();
});

test('detailed health check requires admin role', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)
        ->get('/api/health/detailed');

    $response->assertStatus(403);
});

test('admin can access detailed health check', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get('/api/health/detailed');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'checks' => [
                'database',
                'cache',
                'queue',
                'storage',
                'provisioning',
            ],
        ]);
});

test('health check service returns ok when all systems operational', function () {
    $service = app(HealthCheckService::class);

    $result = $service->check();

    expect($result)->toHaveKey('status')
        ->and($result)->toHaveKey('timestamp')
        ->and($result)->toHaveKey('checks')
        ->and($result['checks'])->toHaveKey('database')
        ->and($result['checks'])->toHaveKey('cache')
        ->and($result['checks'])->toHaveKey('queue')
        ->and($result['checks'])->toHaveKey('storage');
});

test('health check service database check returns ok', function () {
    $service = app(HealthCheckService::class);

    $result = $service->checkDatabase();

    expect($result['status'])->toBe('ok')
        ->and($result)->toHaveKey('response_time_ms');
});

test('health check service cache check returns ok', function () {
    $service = app(HealthCheckService::class);

    $result = $service->checkCache();

    expect($result['status'])->toBe('ok')
        ->and($result)->toHaveKey('driver');
});

test('health check service queue check returns ok', function () {
    $service = app(HealthCheckService::class);

    $result = $service->checkQueue();

    expect($result['status'])->toBe('ok')
        ->and($result)->toHaveKey('driver')
        ->and($result)->toHaveKey('pending_jobs')
        ->and($result)->toHaveKey('failed_jobs');
});

test('health check service storage check returns ok', function () {
    $service = app(HealthCheckService::class);

    $result = $service->checkStorage();

    expect($result['status'])->toBe('ok');
});

test('health check service provisioning check returns valid structure', function () {
    $service = app(HealthCheckService::class);

    $result = $service->checkProvisioningHealth();

    expect($result)->toHaveKey('status')
        ->and($result)->toHaveKey('message');
});

test('ping endpoint returns correct timestamp format', function () {
    $response = $this->getJson('/api/health/ping');

    $response->assertStatus(200);

    $data = $response->json();
    $timestamp = $data['timestamp'];

    expect(fn() => \Carbon\Carbon::parse($timestamp))->not->toThrow(Exception::class);
});
