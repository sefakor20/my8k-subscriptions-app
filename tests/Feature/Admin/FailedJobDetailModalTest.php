<?php

declare(strict_types=1);

use App\Livewire\Admin\FailedJobDetailModal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

// Helper function to create a failed job (reuse from FailedJobsListTest)
function createFailedJobForModal(array $attributes = []): string
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

test('modal opens when dispatched open event', function () {
    $admin = User::factory()->admin()->create();
    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSet('show', true)
        ->assertSet('jobUuid', $uuid);
});

test('modal displays job details correctly', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal([
        'connection' => 'redis',
        'queue' => 'high-priority',
        'exception' => 'Database connection timeout error',
        'failed_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSee('redis')
        ->assertSee('high-priority')
        ->assertSee('Database connection timeout error');
});

test('modal displays full exception stack trace', function () {
    $admin = User::factory()->admin()->create();

    $exception = "Exception: Connection failed\nStack trace:\n#0 /app/Database.php(42): connect()\n#1 /app/Job.php(12): query()";

    $uuid = createFailedJobForModal(['exception' => $exception]);

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSee('Connection failed')
        ->assertSee('Stack trace');
});

test('modal displays job payload', function () {
    $admin = User::factory()->admin()->create();

    $payload = json_encode([
        'displayName' => 'App\\Jobs\\SendEmailJob',
        'job' => 'test',
        'data' => ['email' => 'test@example.com'],
    ]);

    $uuid = createFailedJobForModal(['payload' => $payload]);

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSee('SendEmailJob');
});

test('retry job removes job from failed_jobs table', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->call('retry');

    // Job should be removed from failed_jobs table after retry
    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

test('delete job removes job from database', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->call('delete');

    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

test('modal closes after retry', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSet('show', true)
        ->call('retry')
        ->assertSet('show', false);
});

test('modal closes after delete', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->assertSet('show', true)
        ->call('delete')
        ->assertSet('show', false);
});

test('close modal resets state', function () {
    $admin = User::factory()->admin()->create();

    $uuid = createFailedJobForModal();

    Livewire::actingAs($admin)
        ->test(FailedJobDetailModal::class)
        ->dispatch('open-failed-job-modal', uuid: $uuid)
        ->call('closeModal')
        ->assertSet('show', false)
        ->assertSet('jobUuid', null)
        ->assertSet('job', null);
});
