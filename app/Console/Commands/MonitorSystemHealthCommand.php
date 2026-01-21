<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AlertService;
use App\Services\HealthCheckService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MonitorSystemHealthCommand extends Command
{
    protected $signature = 'system:monitor-health
                            {--test : Send a test alert to verify configuration}
                            {--queue-threshold=100 : Number of pending jobs to trigger warning}
                            {--failed-threshold=10 : Number of failed jobs to trigger warning}';

    protected $description = 'Monitor system health and send alerts for critical issues';

    private const STATUS_DOWN = 'down';

    private const STATUS_DEGRADED = 'degraded';

    public function __construct(
        private readonly HealthCheckService $healthCheckService,
        private readonly AlertService $alertService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('test')) {
            return $this->sendTestAlert();
        }

        $this->info('Running system health check...');

        $issues = [];

        $issues = array_merge($issues, $this->checkCoreHealth());
        $issues = array_merge($issues, $this->checkQueueHealth());
        $issues = array_merge($issues, $this->checkProvisioningHealth());
        $issues = array_merge($issues, $this->checkRecentErrors());

        if (empty($issues)) {
            $this->info('All systems operational.');

            return self::SUCCESS;
        }

        $this->sendAlerts($issues);

        return self::SUCCESS;
    }

    private function sendTestAlert(): int
    {
        $this->info('Sending test alert...');

        $this->alertService->info(
            'Test Alert - System Monitoring',
            'This is a test alert from the system monitoring command. If you receive this, alerts are working correctly.',
            [
                'Command' => 'system:monitor-health --test',
                'Timestamp' => now()->toDateTimeString(),
            ],
        );

        $this->info('Test alert sent. Check your Slack channel.');

        return self::SUCCESS;
    }

    private function checkCoreHealth(): array
    {
        $issues = [];
        $health = $this->healthCheckService->check();

        if ($health['status'] === self::STATUS_DOWN) {
            foreach ($health['checks'] as $service => $check) {
                if ($check['status'] === self::STATUS_DOWN) {
                    $issues[] = [
                        'level' => 'critical',
                        'title' => "Service Down: {$service}",
                        'message' => $check['message'] ?? "The {$service} service is not responding.",
                        'context' => ['service' => $service],
                    ];
                }
            }
        } elseif ($health['status'] === self::STATUS_DEGRADED) {
            foreach ($health['checks'] as $service => $check) {
                if ($check['status'] === self::STATUS_DEGRADED) {
                    $issues[] = [
                        'level' => 'warning',
                        'title' => "Service Degraded: {$service}",
                        'message' => $check['message'] ?? "The {$service} service is experiencing issues.",
                        'context' => ['service' => $service],
                    ];
                }
            }
        }

        return $issues;
    }

    private function checkQueueHealth(): array
    {
        $issues = [];

        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();

            $queueThreshold = (int) $this->option('queue-threshold');
            $failedThreshold = (int) $this->option('failed-threshold');

            if ($pendingJobs > $queueThreshold * 5) {
                $issues[] = [
                    'level' => 'critical',
                    'title' => 'Critical Queue Backlog',
                    'message' => "Queue has {$pendingJobs} pending jobs. This may indicate workers are not running or processing is stuck.",
                    'context' => [
                        'Pending Jobs' => $pendingJobs,
                        'Threshold' => $queueThreshold * 5,
                    ],
                ];
            } elseif ($pendingJobs > $queueThreshold) {
                $issues[] = [
                    'level' => 'warning',
                    'title' => 'Queue Backlog Detected',
                    'message' => "Queue has {$pendingJobs} pending jobs, which exceeds the threshold of {$queueThreshold}.",
                    'context' => [
                        'Pending Jobs' => $pendingJobs,
                        'Threshold' => $queueThreshold,
                    ],
                ];
            }

            if ($failedJobs > $failedThreshold * 5) {
                $issues[] = [
                    'level' => 'critical',
                    'title' => 'High Number of Failed Jobs',
                    'message' => "{$failedJobs} jobs have failed in the last 24 hours. Immediate attention required.",
                    'context' => [
                        'Failed Jobs (24h)' => $failedJobs,
                        'Threshold' => $failedThreshold * 5,
                    ],
                ];
            } elseif ($failedJobs > $failedThreshold) {
                $issues[] = [
                    'level' => 'warning',
                    'title' => 'Elevated Failed Jobs',
                    'message' => "{$failedJobs} jobs have failed in the last 24 hours.",
                    'context' => [
                        'Failed Jobs (24h)' => $failedJobs,
                        'Threshold' => $failedThreshold,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $this->error('Failed to check queue health: ' . $e->getMessage());
        }

        return $issues;
    }

    private function checkProvisioningHealth(): array
    {
        $issues = [];

        $provisioningCheck = $this->healthCheckService->checkProvisioningHealth();

        if ($provisioningCheck['status'] === self::STATUS_DOWN) {
            $issues[] = [
                'level' => 'critical',
                'title' => 'Provisioning System Critical',
                'message' => $provisioningCheck['message'],
                'context' => [
                    'Success Rate' => ($provisioningCheck['success_rate'] ?? 'N/A') . '%',
                    'Total Attempts' => $provisioningCheck['total_attempts'] ?? 'N/A',
                    'Failed Attempts' => $provisioningCheck['failed_attempts'] ?? 'N/A',
                ],
            ];
        } elseif ($provisioningCheck['status'] === self::STATUS_DEGRADED) {
            $issues[] = [
                'level' => 'warning',
                'title' => 'Provisioning Success Rate Low',
                'message' => $provisioningCheck['message'],
                'context' => [
                    'Success Rate' => ($provisioningCheck['success_rate'] ?? 'N/A') . '%',
                    'Total Attempts' => $provisioningCheck['total_attempts'] ?? 'N/A',
                    'Failed Attempts' => $provisioningCheck['failed_attempts'] ?? 'N/A',
                ],
            ];
        }

        return $issues;
    }

    private function checkRecentErrors(): array
    {
        $issues = [];

        try {
            $recentErrors = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subMinutes(30))
                ->count();

            if ($recentErrors >= 5) {
                $issues[] = [
                    'level' => 'critical',
                    'title' => 'Spike in Job Failures',
                    'message' => "{$recentErrors} jobs have failed in the last 30 minutes. This may indicate a systemic issue.",
                    'context' => [
                        'Failed Jobs (30min)' => $recentErrors,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $this->error('Failed to check recent errors: ' . $e->getMessage());
        }

        return $issues;
    }

    private function sendAlerts(array $issues): void
    {
        foreach ($issues as $issue) {
            $this->warn("[{$issue['level']}] {$issue['title']}: {$issue['message']}");

            match ($issue['level']) {
                'critical' => $this->alertService->critical($issue['title'], $issue['message'], $issue['context']),
                'warning' => $this->alertService->warning($issue['title'], $issue['message'], $issue['context']),
                default => $this->alertService->info($issue['title'], $issue['message'], $issue['context']),
            };
        }

        $this->info('Sent ' . count($issues) . ' alert(s).');
    }
}
