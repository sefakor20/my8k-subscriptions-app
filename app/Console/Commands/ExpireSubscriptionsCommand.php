<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ServiceAccountStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:expire
                            {--dry-run : Run without making any changes}
                            {--limit= : Limit number of subscriptions to process}';

    /**
     * The console command description.
     */
    protected $description = 'Expire subscriptions that have passed their expiration date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('Starting subscription expiration check...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find active subscriptions that have expired
        $query = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '<', now())
            ->with(['serviceAccount', 'user']);

        if ($limit) {
            $query->limit((int) $limit);
        }

        $expiredSubscriptions = $query->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('✅ No expired subscriptions found');

            return self::SUCCESS;
        }

        $this->info("Found {$expiredSubscriptions->count()} expired subscription(s)");

        $this->newLine();

        $processedCount = 0;
        $errorCount = 0;

        foreach ($expiredSubscriptions as $subscription) {
            try {
                $this->processSubscription($subscription, $dryRun);
                $processedCount++;
            } catch (Exception $e) {
                $errorCount++;
                $this->error("Failed to process subscription {$subscription->id}: {$e->getMessage()}");

                Log::error('Failed to expire subscription', [
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

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred");
        }

        Log::info('Subscription expiration check completed', [
            'processed' => $processedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
        ]);

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single expired subscription
     */
    private function processSubscription(Subscription $subscription, bool $dryRun): void
    {
        $userEmail = $subscription->user->email ?? 'N/A';
        $expiredDays = now()->diffInDays($subscription->expires_at);

        $this->line(sprintf(
            '  Expiring: Subscription %s (User: %s, Expired: %d days ago)',
            $subscription->id,
            $userEmail,
            $expiredDays,
        ));

        if ($dryRun) {
            return;
        }

        DB::transaction(function () use ($subscription): void {
            // Update subscription status to Expired
            $subscription->update([
                'status' => SubscriptionStatus::Expired,
                'auto_renew' => false,
            ]);

            // Update service account status if exists
            if ($subscription->serviceAccount) {
                $subscription->serviceAccount->update([
                    'status' => ServiceAccountStatus::Expired,
                ]);

                $this->line('    → Service account marked as expired');
            }

            $this->line('    → Subscription marked as expired');
        });
    }
}
