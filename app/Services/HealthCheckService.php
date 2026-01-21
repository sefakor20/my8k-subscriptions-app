<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProvisioningLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckService
{
    private const STATUS_OK = 'ok';

    private const STATUS_DEGRADED = 'degraded';

    private const STATUS_DOWN = 'down';

    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        return [
            'status' => $this->getOverallStatus($checks),
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ];
    }

    public function ping(): array
    {
        return [
            'status' => self::STATUS_OK,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => self::STATUS_OK,
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime,
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::STATUS_DOWN,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    public function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => self::STATUS_DEGRADED,
                    'message' => 'Cache read/write mismatch',
                ];
            }

            return [
                'status' => self::STATUS_OK,
                'message' => 'Cache is operational',
                'driver' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::STATUS_DOWN,
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }

    public function checkQueue(): array
    {
        try {
            $queueConnection = config('queue.default');
            $pendingJobs = 0;
            $failedJobs = 0;

            if ($queueConnection === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
            }

            $status = self::STATUS_OK;
            $message = 'Queue is operational';

            if ($failedJobs > 50) {
                $status = self::STATUS_DEGRADED;
                $message = "High number of failed jobs: {$failedJobs}";
            }

            if ($pendingJobs > 1000) {
                $status = self::STATUS_DEGRADED;
                $message = "Queue backlog detected: {$pendingJobs} pending jobs";
            }

            return [
                'status' => $status,
                'message' => $message,
                'driver' => $queueConnection,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::STATUS_DOWN,
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
        }
    }

    public function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_' . uniqid() . '.txt';
            $testContent = 'health check';

            $disk->put($testFile, $testContent);
            $retrieved = $disk->get($testFile);
            $disk->delete($testFile);

            if ($retrieved !== $testContent) {
                return [
                    'status' => self::STATUS_DEGRADED,
                    'message' => 'Storage read/write mismatch',
                ];
            }

            return [
                'status' => self::STATUS_OK,
                'message' => 'Storage is operational',
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::STATUS_DOWN,
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    public function checkProvisioningHealth(): array
    {
        try {
            $last24Hours = now()->subDay();

            $totalAttempts = ProvisioningLog::where('created_at', '>=', $last24Hours)->count();
            $failedAttempts = ProvisioningLog::where('created_at', '>=', $last24Hours)
                ->where('status', 'failed')
                ->count();

            if ($totalAttempts === 0) {
                return [
                    'status' => self::STATUS_OK,
                    'message' => 'No provisioning attempts in last 24 hours',
                    'success_rate' => null,
                ];
            }

            $successRate = round((($totalAttempts - $failedAttempts) / $totalAttempts) * 100, 2);

            $status = self::STATUS_OK;
            $message = "Provisioning success rate: {$successRate}%";

            if ($successRate < 95) {
                $status = self::STATUS_DEGRADED;
                $message = "Low provisioning success rate: {$successRate}%";
            }

            if ($successRate < 80) {
                $status = self::STATUS_DOWN;
                $message = "Critical: Provisioning success rate is {$successRate}%";
            }

            return [
                'status' => $status,
                'message' => $message,
                'total_attempts' => $totalAttempts,
                'failed_attempts' => $failedAttempts,
                'success_rate' => $successRate,
            ];
        } catch (Throwable $e) {
            return [
                'status' => self::STATUS_DEGRADED,
                'message' => 'Could not check provisioning health: ' . $e->getMessage(),
            ];
        }
    }

    private function getOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array(self::STATUS_DOWN, $statuses, true)) {
            return self::STATUS_DOWN;
        }

        if (in_array(self::STATUS_DEGRADED, $statuses, true)) {
            return self::STATUS_DEGRADED;
        }

        return self::STATUS_OK;
    }
}
