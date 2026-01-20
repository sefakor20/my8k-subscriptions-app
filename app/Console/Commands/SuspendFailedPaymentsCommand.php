<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SuspendAccountJob;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SuspendFailedPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:suspend-failed-payments
                            {--dry-run : Show what would be suspended without actually suspending}
                            {--limit=100 : Maximum number of subscriptions to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suspend subscriptions with failed payments after grace period has expired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '[DRY RUN] ' : '' . 'Checking for subscriptions ready for suspension...');

        $subscriptions = Subscription::readyForSuspension()
            ->with(['user', 'serviceAccount'])
            ->limit($limit)
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions found that need to be suspended.');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to suspend.");

        $suspendedCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->line("Processing subscription {$subscription->id} (User: {$subscription->user->email})");

            if (! $subscription->serviceAccount) {
                $this->warn("  - Skipped: No service account linked");
                $errorCount++;

                continue;
            }

            if ($dryRun) {
                $this->info("  - [DRY RUN] Would suspend service account {$subscription->serviceAccount->id}");
                $suspendedCount++;

                continue;
            }

            try {
                // Dispatch the suspension job
                SuspendAccountJob::dispatch(
                    $subscription->id,
                    $subscription->serviceAccount->id,
                    'Payment failed - grace period expired',
                );

                $this->info("  - Dispatched suspension job");
                $suspendedCount++;

                Log::info('Suspended subscription due to payment failure', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'service_account_id' => $subscription->serviceAccount->id,
                    'payment_failure_count' => $subscription->payment_failure_count,
                    'expired_at' => $subscription->expires_at->toIso8601String(),
                ]);
            } catch (Throwable $e) {
                $this->error("  - Failed to dispatch suspension: {$e->getMessage()}");
                $errorCount++;

                Log::error('Failed to suspend subscription due to payment failure', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Completed: {$suspendedCount} suspended, {$errorCount} errors");

        return self::SUCCESS;
    }
}
