<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Models\ProvisioningLog;
use App\Models\ServiceAccount;
use App\Models\Subscription;
use App\Services\Admin\ResellerCreditsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseProvisioningJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of retry attempts
     */
    public int $tries = 5;

    /**
     * Exponential backoff delays in seconds: 10s, 30s, 90s, 270s, 810s
     */
    public array $backoff = [10, 30, 90, 270, 810];

    /**
     * Job timeout in seconds
     */
    public int $timeout = 60;

    /**
     * Get the provisioning action type for this job
     */
    abstract protected function getProvisioningAction(): ProvisioningAction;

    /**
     * Perform the actual provisioning operation
     * Must return array with 'success', 'data'/'error', 'duration_ms'
     */
    abstract protected function performProvisioning(): array;

    /**
     * Get related models for logging
     */
    abstract protected function getRelatedModels(): array;

    /**
     * Execute the provisioning job with error handling and logging
     */
    public function handle(): void
    {
        $attemptNumber = $this->attempts();
        $startTime = microtime(true);

        try {
            // Perform the provisioning operation
            $result = $this->performProvisioning();

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $this->handleSuccess($result, $attemptNumber, $durationMs);
            } else {
                $this->handleFailure($result, $attemptNumber, $durationMs);
            }
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->handleException($e, $attemptNumber, $durationMs);
        }
    }

    /**
     * Handle successful provisioning
     */
    protected function handleSuccess(array $result, int $attemptNumber, int $durationMs): void
    {
        $models = $this->getRelatedModels();

        $provisioningLog = $this->createProvisioningLog(
            status: ProvisioningStatus::Success,
            attemptNumber: $attemptNumber,
            request: $result['request'] ?? [],
            response: $result['data'] ?? [],
            durationMs: $durationMs,
        );

        Log::info('Provisioning successful', [
            'action' => $this->getProvisioningAction()->value,
            'attempt' => $attemptNumber,
            'duration_ms' => $durationMs,
            'subscription_id' => $models['subscription_id'] ?? null,
            'order_id' => $models['order_id'] ?? null,
        ]);

        // Log credit usage after successful provisioning
        $this->logCreditUsage($provisioningLog);
    }

    /**
     * Log credit usage after successful provisioning
     */
    protected function logCreditUsage(ProvisioningLog $provisioningLog): void
    {
        try {
            $creditsService = app(ResellerCreditsService::class);
            $reason = sprintf(
                '%s operation for subscription %s',
                $this->getProvisioningAction()->label(),
                $provisioningLog->subscription_id ?? 'unknown',
            );

            $creditsService->logBalanceSnapshot(
                reason: $reason,
                provisioningLogId: (int) $provisioningLog->id,
            );

            Log::debug('Credit usage logged', [
                'provisioning_log_id' => $provisioningLog->id,
                'action' => $this->getProvisioningAction()->value,
            ]);
        } catch (Exception $e) {
            // Don't fail the job if credit logging fails
            Log::warning('Failed to log credit usage', [
                'provisioning_log_id' => $provisioningLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle provisioning failure (will retry if retryable)
     */
    protected function handleFailure(array $result, int $attemptNumber, int $durationMs): void
    {
        $models = $this->getRelatedModels();
        $isRetryable = $result['retryable'] ?? false;

        $status = $isRetryable && $attemptNumber < $this->tries
            ? ProvisioningStatus::Retrying
            : ProvisioningStatus::Failed;

        $this->createProvisioningLog(
            status: $status,
            attemptNumber: $attemptNumber,
            request: $result['request'] ?? [],
            response: $result['response_body'] ?? [],
            errorMessage: $result['error'] ?? 'Unknown error',
            errorCode: $result['error_code'] ?? 'ERR_UNKNOWN',
            durationMs: $durationMs,
        );

        Log::warning('Provisioning failed', [
            'action' => $this->getProvisioningAction()->value,
            'attempt' => $attemptNumber,
            'error' => $result['error'] ?? 'Unknown error',
            'error_code' => $result['error_code'] ?? 'ERR_UNKNOWN',
            'retryable' => $isRetryable,
            'will_retry' => $isRetryable && $attemptNumber < $this->tries,
            'subscription_id' => $models['subscription_id'] ?? null,
            'order_id' => $models['order_id'] ?? null,
        ]);

        // If retryable and not at max attempts, throw to trigger retry
        if ($isRetryable && $attemptNumber < $this->tries) {
            throw new Exception($result['error'] ?? 'Provisioning failed');
        }

        // Final failure - call child class hook
        $this->onFinalFailure($result);
    }

    /**
     * Handle unexpected exceptions
     */
    protected function handleException(Exception $e, int $attemptNumber, int $durationMs): void
    {
        $models = $this->getRelatedModels();

        $status = $attemptNumber < $this->tries
            ? ProvisioningStatus::Retrying
            : ProvisioningStatus::Failed;

        $this->createProvisioningLog(
            status: $status,
            attemptNumber: $attemptNumber,
            request: [],
            response: [],
            errorMessage: $e->getMessage(),
            errorCode: 'ERR_EXCEPTION',
            durationMs: $durationMs,
        );

        Log::error('Provisioning exception', [
            'action' => $this->getProvisioningAction()->value,
            'attempt' => $attemptNumber,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'subscription_id' => $models['subscription_id'] ?? null,
            'order_id' => $models['order_id'] ?? null,
        ]);

        // Rethrow to trigger retry
        throw $e;
    }

    /**
     * Create a provisioning log entry
     */
    protected function createProvisioningLog(
        ProvisioningStatus $status,
        int $attemptNumber,
        array $request,
        array $response,
        ?string $errorMessage = null,
        ?string $errorCode = null,
        int $durationMs = 0,
    ): ProvisioningLog {
        $models = $this->getRelatedModels();

        return ProvisioningLog::create([
            'subscription_id' => $models['subscription_id'] ?? null,
            'order_id' => $models['order_id'] ?? null,
            'service_account_id' => $models['service_account_id'] ?? null,
            'action' => $this->getProvisioningAction(),
            'status' => $status,
            'attempt_number' => $attemptNumber,
            'job_id' => $this->job?->getJobId(),
            'my8k_request' => $request,
            'my8k_response' => $response,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Hook called when job finally fails after all retries
     * Override in child classes to handle final failure
     */
    protected function onFinalFailure(array $result): void
    {
        // Override in child classes
    }

    /**
     * Get subscription if available
     */
    protected function getSubscription(): ?Subscription
    {
        $models = $this->getRelatedModels();

        return $models['subscription_id'] ? Subscription::find($models['subscription_id']) : null;
    }

    /**
     * Get order if available
     */
    protected function getOrder(): ?Order
    {
        $models = $this->getRelatedModels();

        return $models['order_id'] ? Order::find($models['order_id']) : null;
    }

    /**
     * Get service account if available
     */
    protected function getServiceAccount(): ?ServiceAccount
    {
        $models = $this->getRelatedModels();

        return $models['service_account_id'] ? ServiceAccount::find($models['service_account_id']) : null;
    }
}
