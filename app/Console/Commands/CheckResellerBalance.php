<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ResellerCreditAlert;
use App\Services\Admin\ResellerCreditsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Exception;

class CheckResellerBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:check-balance {--force : Send alert even if already sent recently}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check reseller credit balance and send alerts if below thresholds';

    /**
     * Execute the console command.
     */
    public function handle(ResellerCreditsService $creditsService): int
    {
        $this->info('Checking reseller credit balance...');

        try {
            // Log balance snapshot
            $log = $creditsService->logBalanceSnapshot('Scheduled balance check');

            $this->info("Current Balance: {$log->balance} credits");

            // Get metrics for alert evaluation
            $metrics = $creditsService->calculateUsageMetrics();
            $alertLevel = $metrics['alertLevel'];

            $this->info("Alert Level: {$alertLevel}");

            // Check if alert should be triggered
            if ($creditsService->shouldTriggerAlert($metrics['currentBalance'])) {
                // Check if we've sent an alert recently (within last 12 hours)
                if (!$this->option('force') && $this->hasRecentAlert()) {
                    $this->warn('Alert already sent within the last 12 hours. Use --force to send anyway.');

                    return self::SUCCESS;
                }

                // Send alert to all admin users
                $this->info('Sending alert notifications to admin users...');

                $admins = User::where('is_admin', true)->get();

                Notification::send($admins, new ResellerCreditAlert(
                    currentBalance: $metrics['currentBalance'],
                    alertLevel: $alertLevel,
                    estimatedDepletionDays: $metrics['estimatedDepletionDays'],
                ));

                // Store timestamp of last alert
                cache()->put('reseller_credits.last_alert', now(), now()->addHours(12));

                $this->info("Alert sent to {$admins->count()} admin user(s).");
            } else {
                $this->info('Balance is healthy. No alerts needed.');
            }

            $this->newLine();
            $this->info('✓ Balance check completed successfully');

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to check reseller balance: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Check if an alert was sent recently
     */
    private function hasRecentAlert(): bool
    {
        return cache()->has('reseller_credits.last_alert');
    }
}
