<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionExpiringSoon;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendExpiryRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:send-expiry-reminders
                            {--dry-run : Run without sending emails}
                            {--days=7 : Days before expiry to send reminder}';

    /**
     * The console command description.
     */
    protected $description = 'Send expiry reminder emails to customers with subscriptions expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $daysBeforeExpiry = (int) $this->option('days');

        $this->info('Starting expiry reminder email process...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No emails will be sent');
        }

        $targetDate = now()->addDays($daysBeforeExpiry);

        // Find active subscriptions expiring on the target date
        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereDate('expires_at', $targetDate->toDateString())
            ->with(['user', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info("✅ No subscriptions expiring in {$daysBeforeExpiry} days");

            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) expiring in {$daysBeforeExpiry} days");
        $this->newLine();

        $sentCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            // Skip if auto-renew is enabled (customer doesn't need reminder)
            if ($subscription->auto_renew) {
                $skippedCount++;
                $this->line("  Skipped: Subscription {$subscription->id} (auto-renew enabled)");

                continue;
            }

            if ($dryRun) {
                $this->line("  Would send to: {$subscription->user->email} (Subscription {$subscription->id})");
                $sentCount++;

                continue;
            }

            try {
                Mail::to($subscription->user->email)
                    ->send(new SubscriptionExpiringSoon($subscription, $daysBeforeExpiry));

                $sentCount++;
                $this->line("  Sent to: {$subscription->user->email} (Subscription {$subscription->id})");

                Log::info('Expiry reminder email sent', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'expires_at' => $subscription->expires_at,
                ]);
            } catch (Exception $e) {
                $errorCount++;
                $this->error("  Failed: {$subscription->user->email} - {$e->getMessage()}");

                Log::error('Failed to send expiry reminder email', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 DRY RUN: Would have sent {$sentCount} email(s)");
        } else {
            $this->info("✅ Successfully sent {$sentCount} email(s)");
        }

        if ($skippedCount > 0) {
            $this->info("↪  Skipped {$skippedCount} subscription(s) (auto-renew enabled)");
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred");
        }

        Log::info('Expiry reminder process completed', [
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
            'days_before_expiry' => $daysBeforeExpiry,
        ]);

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
