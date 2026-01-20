<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SuspensionWarning;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSuspensionWarningsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-suspension-warnings
                            {--dry-run : Show what would be sent without actually sending}
                            {--days=2 : Send warning to subscriptions expiring within this many days}
                            {--limit=100 : Maximum number of warnings to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send suspension warning emails to subscriptions with failed payments nearing expiry';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '[DRY RUN] ' : '' . "Checking for subscriptions needing suspension warnings (expiring within {$days} days)...");

        $subscriptions = Subscription::needingSuspensionWarning($days)
            ->with(['user', 'plan'])
            ->limit($limit)
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions found that need suspension warnings.');

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to warn.");

        $sentCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->line("Processing subscription {$subscription->id} (User: {$subscription->user->email})");
            $this->line("  - Expires: {$subscription->expires_at->format('Y-m-d H:i')} ({$subscription->daysUntilExpiry()} days remaining)");

            if ($dryRun) {
                $this->info("  - [DRY RUN] Would send warning email");
                $sentCount++;

                continue;
            }

            try {
                // Send the warning email
                Mail::to($subscription->user->email)->queue(new SuspensionWarning($subscription));

                // Mark warning as sent
                $subscription->markSuspensionWarningSent();

                $this->info("  - Warning email queued");
                $sentCount++;

                Log::info('Suspension warning email sent', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'email' => $subscription->user->email,
                    'expires_at' => $subscription->expires_at->toIso8601String(),
                    'days_remaining' => $subscription->daysUntilExpiry(),
                ]);
            } catch (Throwable $e) {
                $this->error("  - Failed to send warning: {$e->getMessage()}");
                $errorCount++;

                Log::error('Failed to send suspension warning email', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Completed: {$sentCount} warnings sent, {$errorCount} errors");

        return self::SUCCESS;
    }
}
