<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProvisioningLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class CleanupOldLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:cleanup
                            {--dry-run : Run without making any changes}
                            {--days=365 : Delete logs older than this many days}';

    /**
     * The console command description.
     */
    protected $description = 'Delete old provisioning logs to free up database space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        $this->info('Starting provisioning logs cleanup...');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $cutoffDate = now()->subDays($days);

        $this->info("Deleting logs older than {$days} days (before {$cutoffDate->toDateString()})...");

        // Count logs to be deleted
        $logsToDeleteCount = ProvisioningLog::where('created_at', '<', $cutoffDate)->count();

        if ($logsToDeleteCount === 0) {
            $this->info('✅ No old logs found to delete');

            return self::SUCCESS;
        }

        $this->warn("Found {$logsToDeleteCount} log(s) to delete");

        if ($dryRun) {
            $this->info("🔍 DRY RUN: Would have deleted {$logsToDeleteCount} log(s)");

            return self::SUCCESS;
        }

        // Confirm deletion if more than 1000 logs
        if ($logsToDeleteCount > 1000 && ! $this->confirm("This will delete {$logsToDeleteCount} logs. Continue?", true)) {
            $this->info('Cleanup cancelled by user');

            return self::SUCCESS;
        }

        try {
            // Delete old logs in chunks to avoid memory issues
            $deletedCount = 0;
            $chunkSize = 1000;

            $this->output->progressStart($logsToDeleteCount);

            while (true) {
                $deleted = ProvisioningLog::where('created_at', '<', $cutoffDate)
                    ->limit($chunkSize)
                    ->delete();

                if ($deleted === 0) {
                    break;
                }

                $deletedCount += $deleted;
                $this->output->progressAdvance($deleted);

                // Small delay to avoid overwhelming the database
                if ($deleted === $chunkSize) {
                    usleep(100000); // 100ms
                }
            }

            $this->output->progressFinish();

            $this->newLine();
            $this->info("✅ Successfully deleted {$deletedCount} log(s)");

            Log::info('Provisioning logs cleanup completed', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'days' => $days,
            ]);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to delete logs: {$e->getMessage()}");

            Log::error('Failed to cleanup provisioning logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
