<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProvisionNewAccountJob;
use App\Models\Order;
use App\Services\WooCommerceApiClient;
use App\Services\WooCommerceWebhookHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class ReconcileWooCommerceOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'woocommerce:reconcile-orders
                            {--dry-run : Run without making any changes}
                            {--days=2 : Number of days to look back for orders}
                            {--limit= : Limit number of orders to process}';

    /**
     * The console command description.
     */
    protected $description = 'Reconcile WooCommerce orders with local database to catch missed webhooks';

    /**
     * Execute the console command.
     */
    public function handle(
        WooCommerceApiClient $woocommerceClient,
        WooCommerceWebhookHandler $webhookHandler,
    ): int {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $limit = $this->option('limit');

        $this->info('Starting WooCommerce order reconciliation...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Calculate date range
        $modifiedAfter = now()->subDays($days)->toIso8601String();

        $this->info("Fetching orders modified in last {$days} days (since {$modifiedAfter})...");

        // Fetch orders from WooCommerce
        $params = [
            'status' => 'completed',
            'modified_after' => $modifiedAfter,
            'per_page' => $limit ? (int) $limit : 100,
            'orderby' => 'modified',
            'order' => 'desc',
        ];

        $result = $woocommerceClient->getOrders($params);

        if (! $result['success']) {
            $this->error("Failed to fetch orders from WooCommerce: {$result['error']}");
            $this->error("Error code: {$result['error_code']}");

            Log::error('WooCommerce order reconciliation failed', [
                'error' => $result['error'],
                'error_code' => $result['error_code'],
            ]);

            return self::FAILURE;
        }

        $woocommerceOrders = $result['data'];
        $totalFetched = count($woocommerceOrders);

        if ($totalFetched === 0) {
            $this->info('✅ No completed orders found in WooCommerce for the specified period');

            return self::SUCCESS;
        }

        $this->info("Found {$totalFetched} completed order(s) in WooCommerce");
        $this->newLine();

        $missingCount = 0;
        $existingCount = 0;
        $processedCount = 0;
        $errorCount = 0;

        foreach ($woocommerceOrders as $woocommerceOrder) {
            $woocommerceOrderId = (string) $woocommerceOrder['id'];

            // Check if order exists locally
            $localOrder = Order::where('woocommerce_order_id', $woocommerceOrderId)->first();

            if ($localOrder) {
                $existingCount++;

                continue;
            }

            // Order is missing locally
            $missingCount++;

            $this->warn("  Missing: Order #{$woocommerceOrderId} (Date: {$woocommerceOrder['date_created']})");

            if ($dryRun) {
                continue;
            }

            // Process the missing order
            try {
                $result = $webhookHandler->processOrderCompleted($woocommerceOrder);

                if ($result['duplicate'] ?? false) {
                    $this->line('    → Order already exists (caught by idempotency check)');
                    $existingCount++;
                    $missingCount--;

                    continue;
                }

                // Dispatch provisioning job
                ProvisionNewAccountJob::dispatch(
                    orderId: $result['order_id'],
                    subscriptionId: $result['subscription_id'],
                    planId: $result['plan_id'],
                );

                // Add note to WooCommerce order
                $noteResult = $woocommerceClient->addOrderNote(
                    $woocommerceOrderId,
                    'Order reconciled via scheduled task. Provisioning job dispatched.',
                    false,
                );

                if (! $noteResult['success']) {
                    $this->warn("    → Failed to add note to WooCommerce order: {$noteResult['error']}");
                }

                $this->line('    → Order created and provisioning job dispatched');

                $processedCount++;

                Log::info('Reconciled missing WooCommerce order', [
                    'woocommerce_order_id' => $woocommerceOrderId,
                    'order_id' => $result['order_id'],
                    'subscription_id' => $result['subscription_id'],
                ]);
            } catch (Exception $e) {
                $errorCount++;
                $this->error("    → Failed to process order: {$e->getMessage()}");

                Log::error('Failed to reconcile WooCommerce order', [
                    'woocommerce_order_id' => $woocommerceOrderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();

        // Summary
        $this->info('Reconciliation Summary:');
        $this->line("  Total orders fetched: {$totalFetched}");
        $this->line("  Existing orders: {$existingCount}");

        if ($missingCount > 0) {
            $this->warn("  Missing orders: {$missingCount}");
        } else {
            $this->info("  Missing orders: {$missingCount}");
        }

        if ($dryRun) {
            $this->info("🔍 DRY RUN: Would have processed {$missingCount} missing order(s)");
        } else {
            $this->info("✅ Successfully processed {$processedCount} missing order(s)");
        }

        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} error(s) occurred");
        }

        // Log summary
        Log::info('WooCommerce order reconciliation completed', [
            'total_fetched' => $totalFetched,
            'existing' => $existingCount,
            'missing' => $missingCount,
            'processed' => $processedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
            'days' => $days,
        ]);

        // Alert if many orders are missing
        if ($missingCount > 5 && ! $dryRun) {
            $this->error('⚠️  ALERT: More than 5 orders were missing! This may indicate webhook delivery issues.');

            Log::critical('High number of missing WooCommerce orders detected', [
                'missing_count' => $missingCount,
                'total_fetched' => $totalFetched,
            ]);
        }

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
