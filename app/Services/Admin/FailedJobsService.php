<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class FailedJobsService
{
    /**
     * Get failed jobs with filters and pagination
     */
    public function getFailedJobsWithFilters(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = DB::table('failed_jobs')->orderBy('failed_at', 'desc');

        // Apply job type filter
        if (! empty($filters['job_type'])) {
            $query->where('queue', 'like', "%{$filters['job_type']}%");
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('failed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('failed_at', '<=', $filters['date_to']);
        }

        // Apply error search filter
        if (! empty($filters['error_search'])) {
            $search = $filters['error_search'];
            $query->where('exception', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a single failed job by UUID
     */
    public function getFailedJob(string $uuid): ?object
    {
        return DB::table('failed_jobs')->where('uuid', $uuid)->first();
    }

    /**
     * Get distinct job types for filter dropdown
     */
    public function getDistinctJobTypes(): Collection
    {
        return DB::table('failed_jobs')
            ->select('queue')
            ->distinct()
            ->pluck('queue')
            ->filter();
    }

    /**
     * Retry a single failed job
     */
    public function retryJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    /**
     * Retry multiple failed jobs
     */
    public function retryJobs(array $uuids): void
    {
        foreach ($uuids as $uuid) {
            $this->retryJob($uuid);
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAll(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
    }

    /**
     * Delete a single failed job
     */
    public function deleteJob(string $uuid): void
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();
    }

    /**
     * Delete multiple failed jobs
     */
    public function deleteJobs(array $uuids): void
    {
        DB::table('failed_jobs')->whereIn('uuid', $uuids)->delete();
    }

    /**
     * Delete all failed jobs
     */
    public function deleteAll(): void
    {
        DB::table('failed_jobs')->truncate();
    }

    /**
     * Get failed jobs count
     */
    public function getCount(): int
    {
        return DB::table('failed_jobs')->count();
    }
}
