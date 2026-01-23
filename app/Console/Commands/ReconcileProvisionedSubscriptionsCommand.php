<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileProvisionedSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:reconcile-provisioned-status
                            {--dry-run : Run without making any changes}
                            {--limit= : Limit number of subscriptions to process}';

    /**
     * The console command description.
     */
    protected $description = 'Reconcile subscriptions with provisioned accounts but pending status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('Starting subscription status reconciliation...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find subscriptions with service_account_id but still pending status
        $query = Subscription::query()
            ->whereNotNull('service_account_id')
            ->where('status', SubscriptionStatus::Pending)
            ->with(['serviceAccount', 'user']);

        if ($limit) {
            $query->limit((int) $limit);
        }

        $subscriptionsToReconcile = $query->get();

        if ($subscriptionsToReconcile->isEmpty()) {
            $this->info('✅ No subscriptions need reconciliation');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptionsToReconcile->count()} subscription(s) with inconsistent status");

        $this->newLine();

        $processedCount = 0;
        $errorCount = 0;
        $orphanedCount = 0;

        foreach ($subscriptionsToReconcile as $subscription) {
            try {
                $result = $this->processSubscription($subscription, $dryRun);

                if ($result === 'orphaned') {
                    $orphanedCount++;
                } else {
                    $processedCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
                $this->error("Failed to process subscription {$subscription->id}: {$e->getMessage()}");

                Log::error('Failed to reconcile subscription status', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 DRY RUN: Would have processed {$processedCount} subscription(s)");
        } else {
            $this->info("✅ Successfully processed {$processedCount} subscription(s)");
        }

        if ($orphanedCount > 0) {
            $this->warn("⚠️  {$orphanedCount} orphaned subscription(s) found (ServiceAccount deleted)");
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred");
        }

        Log::info('Subscription status reconciliation completed', [
            'processed' => $processedCount,
            'orphaned' => $orphanedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
        ]);

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single subscription
     */
    private function processSubscription(Subscription $subscription, bool $dryRun): string
    {
        $userEmail = $subscription->user->email ?? 'N/A';

        // Check if ServiceAccount exists
        if (! $subscription->serviceAccount) {
            $this->line(sprintf(
                '  Orphaned: Subscription %s (User: %s, ServiceAccount: %s - DELETED)',
                $subscription->id,
                $userEmail,
                $subscription->service_account_id,
            ));

            Log::warning('Orphaned subscription found', [
                'subscription_id' => $subscription->id,
                'service_account_id' => $subscription->service_account_id,
                'user_email' => $userEmail,
            ]);

            return 'orphaned';
        }

        // Check if ServiceAccount is already expired
        if ($subscription->serviceAccount->expires_at && $subscription->serviceAccount->expires_at < now()) {
            $this->line(sprintf(
                '  Skipping: Subscription %s (User: %s) - ServiceAccount expired',
                $subscription->id,
                $userEmail,
            ));

            return 'skipped';
        }

        $this->line(sprintf(
            '  Reconciling: Subscription %s (User: %s, ServiceAccount: %s)',
            $subscription->id,
            $userEmail,
            $subscription->serviceAccount->id,
        ));

        if ($dryRun) {
            $this->line('    → Would update subscription status: Pending → Active');

            if ($subscription->serviceAccount->status !== ServiceAccountStatus::Active) {
                $this->line('    → Would update ServiceAccount status: ' . $subscription->serviceAccount->status->value . ' → Active');
            } else {
                $this->line('    → ServiceAccount status already Active');
            }

            return 'processed';
        }

        $serviceAccountNeedsUpdate = $subscription->serviceAccount->status !== ServiceAccountStatus::Active;
        $originalServiceAccountStatus = $subscription->serviceAccount->status->value;

        DB::transaction(function () use ($subscription): void {
            // Update subscription status
            $subscription->update([
                'status' => SubscriptionStatus::Active,
            ]);

            // Update ServiceAccount status if needed
            if ($subscription->serviceAccount->status !== ServiceAccountStatus::Active) {
                $subscription->serviceAccount->update([
                    'status' => ServiceAccountStatus::Active,
                ]);
            }
        });

        $this->line('    → Subscription status updated: Pending → Active');

        if ($serviceAccountNeedsUpdate) {
            $this->line("    → ServiceAccount status updated: {$originalServiceAccountStatus} → Active");
        } else {
            $this->line('    → ServiceAccount status already Active');
        }

        return 'processed';
    }
}
