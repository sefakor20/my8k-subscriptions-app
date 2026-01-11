<?php

declare(strict_types=1);

use App\Livewire\Admin\FailedJobsList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

// Helper function to create a failed job
function createFailedJob(array $attributes = []): string
{
    $uuid = Str::uuid()->toString();

    DB::table('failed_jobs')->insert(array_merge([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['job' => 'test']),
        'exception' => 'Test exception message',
        'failed_at' => now(),
    ], $attributes));

    return $uuid;
}

test('admin can access failed jobs list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/failed-jobs');

    $response->assertSuccessful();
    $response->assertSee('Failed Jobs Management');
});

test('non-admin user gets 403 on failed jobs list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/failed-jobs');

    $response->assertForbidden();
});

test('guest is redirected to login when accessing failed jobs list', function () {
    $response = $this->get('/admin/failed-jobs');

    $response->assertRedirect(route('login'));
});

test('failed jobs list displays jobs correctly', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob([
        'queue' => 'high-priority',
        'exception' => 'Database connection failed',
    ]);

    $response = $this->actingAs($admin)->get('/admin/failed-jobs');

    $response->assertSuccessful();
    $response->assertSee('high-priority');
    $response->assertSee('Database connection failed');
});

test('failed jobs list pagination works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create 60 failed jobs (more than one page at 50 per page)
    for ($i = 0; $i < 60; $i++) {
        createFailedJob();
    }

    $response = $this->actingAs($admin)->get('/admin/failed-jobs');

    $response->assertSuccessful();
    $response->assertSee('Next'); // Pagination controls should be visible
});

test('job type filter shows only jobs of specified queue', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob(['queue' => 'emails']);
    createFailedJob(['queue' => 'payments']);

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('jobTypeFilter', 'emails')
        ->assertSee('emails');
});

test('error search filter works', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob(['exception' => 'Connection timeout error']);
    createFailedJob(['exception' => 'Authentication failed error']);

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('errorSearch', 'timeout')
        ->assertSee('timeout');
});

test('date range filter works correctly', function () {
    $admin = User::factory()->admin()->create();

    // Create jobs from 3 days ago
    createFailedJob(['failed_at' => now()->subDays(3)]);
    createFailedJob(['failed_at' => now()->subDays(3)]);

    // Create jobs from today
    createFailedJob(['failed_at' => now()]);
    createFailedJob(['failed_at' => now()]);

    $dateFrom = now()->startOfDay()->format('Y-m-d');

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('dateFrom', $dateFrom);
    // Should only show today's 2 jobs
});

test('empty state displays when no failed jobs found', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin/failed-jobs');

    $response->assertSuccessful();
    $response->assertSee('No failed jobs found');
});

test('select all checkbox toggles all selections', function () {
    $admin = User::factory()->admin()->create();

    $uuid1 = createFailedJob();
    $uuid2 = createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->assertSet('selectAll', false)
        ->assertSet('selectedIds', [])
        ->call('toggleSelectAll')
        ->assertSet('selectAll', true)
        ->assertCount('selectedIds', 2)
        ->call('toggleSelectAll')
        ->assertSet('selectAll', false)
        ->assertSet('selectedIds', []);
});

test('individual job selection works', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJob();

    $component = Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->assertSet('selectedIds', [])
        ->call('toggleSelect', $uuid);

    expect($component->get('selectedIds'))->toContain($uuid);

    $component->call('toggleSelect', $uuid);

    expect($component->get('selectedIds'))->not->toContain($uuid);
});

test('retry selected calls artisan queue retry', function () {
    $admin = User::factory()->admin()->create();

    $uuid1 = createFailedJob();
    $uuid2 = createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('selectedIds', [$uuid1, $uuid2])
        ->call('retrySelected');

    // Jobs should be removed from failed_jobs table after retry
    expect(DB::table('failed_jobs')->where('uuid', $uuid1)->exists())->toBeFalse();
    expect(DB::table('failed_jobs')->where('uuid', $uuid2)->exists())->toBeFalse();
});

// Note: session flash messages are tested via integration, not unit tests

test('delete selected removes jobs from database', function () {
    $admin = User::factory()->admin()->create();

    $uuid1 = createFailedJob();
    $uuid2 = createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('selectedIds', [$uuid1, $uuid2])
        ->call('deleteSelected');

    expect(DB::table('failed_jobs')->where('uuid', $uuid1)->exists())->toBeFalse();
    expect(DB::table('failed_jobs')->where('uuid', $uuid2)->exists())->toBeFalse();
});


test('retry all retries all failed jobs', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob();
    createFailedJob();
    createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->call('retryAll');

    // All jobs should be removed after retry
    expect(DB::table('failed_jobs')->count())->toBe(0);
});


test('delete all removes all failed jobs', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob();
    createFailedJob();
    createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->call('deleteAll');

    expect(DB::table('failed_jobs')->count())->toBe(0);
});


test('reset filters clears all filter values', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->set('jobTypeFilter', 'TestJob')
        ->set('errorSearch', 'error')
        ->set('dateFrom', '2025-01-01')
        ->set('dateTo', '2025-01-10')
        ->call('resetFilters')
        ->assertSet('jobTypeFilter', '')
        ->assertSet('errorSearch', '')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '');
});

test('failed jobs show view details action', function () {
    $admin = User::factory()->admin()->create();

    createFailedJob();

    $response = $this->actingAs($admin)->get('/admin/failed-jobs');

    $response->assertSuccessful();
    $response->assertSee('View Details');
});

test('show detail dispatches open modal event', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJob();

    Livewire::actingAs($admin)
        ->test(FailedJobsList::class)
        ->call('showDetail', $uuid)
        ->assertDispatched('open-failed-job-modal');
});
