<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RenewSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:renew
        {--dry-run : Show what would be renewed without processing}
        {--limit=100 : Maximum subscriptions to process}
        {--subscription= : Renew a specific subscription by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic subscription renewals for due subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionRenewalService $renewalService): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('subscription');

        if ($isDryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        $this->info('Finding subscriptions due for renewal...');

        $subscriptions = $this->getDueSubscriptions($limit, $specificId);

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions due for renewal.');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to process.");
        $this->newLine();

        $successCount = 0;
        $failureCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription, $renewalService, $isDryRun, $successCount, $failureCount);
        }

        $this->newLine();
        $this->info("Renewal complete: {$successCount} succeeded, {$failureCount} failed.");

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get subscriptions that are due for renewal.
     *
     * @return Collection<int, Subscription>
     */
    protected function getDueSubscriptions(int $limit, ?string $specificId): Collection
    {
        $query = Subscription::query()
            ->with(['user', 'plan', 'orders' => fn($q) => $q->latest()->limit(1)])
            ->where('auto_renew', true)
            ->where('status', SubscriptionStatus::Active)
            ->where(function ($query) {
                // Renewal is due if:
                // 1. next_renewal_at is past or today, OR
                // 2. expires_at is within 1 day
                $query->where('next_renewal_at', '<=', now())
                    ->orWhere('expires_at', '<=', now()->addDay());
            });

        if ($specificId) {
            $query->where('id', $specificId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Process a single subscription renewal.
     */
    protected function processSubscription(
        Subscription $subscription,
        SubscriptionRenewalService $renewalService,
        bool $isDryRun,
        int &$successCount,
        int &$failureCount,
    ): void {
        $user = $subscription->user;
        $plan = $subscription->plan;

        $this->line("Processing: {$user->name} ({$user->email}) - {$plan->name}");
        $this->line("  Subscription ID: {$subscription->id}");
        $this->line("  Expires: {$subscription->expires_at->format('Y-m-d H:i')}");
        $this->line("  Next renewal: " . ($subscription->next_renewal_at?->format('Y-m-d H:i') ?? 'Not set'));

        if ($isDryRun) {
            $this->comment('  [DRY RUN] Would process renewal');
            $successCount++;

            return;
        }

        $result = $renewalService->renewSubscription($subscription);

        if ($result['success']) {
            $this->info("  [SUCCESS] Renewed successfully. Order ID: {$result['order']->id}");
            $subscription->refresh();
            $this->line("  New expiry: {$subscription->expires_at->format('Y-m-d H:i')}");
            $successCount++;
        } else {
            $this->error("  [FAILED] {$result['error']}");
            $failureCount++;
        }

        $this->newLine();
    }
}
