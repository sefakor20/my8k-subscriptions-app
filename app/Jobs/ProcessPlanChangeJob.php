<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PlanChangeConfirmed;
use App\Models\PlanChange;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPlanChangeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PlanChange $planChange,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $planChange = $this->planChange->fresh(['subscription', 'user', 'fromPlan', 'toPlan']);

        if (! $planChange || ! $planChange->isCompleted()) {
            Log::warning('ProcessPlanChangeJob: Plan change not found or not completed', [
                'plan_change_id' => $this->planChange->id,
            ]);

            return;
        }

        $subscription = $planChange->subscription;
        $user = $planChange->user;

        Log::info('Processing plan change completion', [
            'plan_change_id' => $planChange->id,
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'from_plan' => $planChange->fromPlan->name,
            'to_plan' => $planChange->toPlan->name,
        ]);

        // Send confirmation email
        $this->sendConfirmationEmail($planChange);

        Log::info('Plan change processing completed', [
            'plan_change_id' => $planChange->id,
        ]);
    }

    /**
     * Send plan change confirmation email.
     */
    private function sendConfirmationEmail(PlanChange $planChange): void
    {
        $notificationService = app(NotificationService::class);
        $notificationService->queueMail(
            $planChange->user,
            new PlanChangeConfirmed($planChange),
            ['plan_change_id' => $planChange->id],
        );
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ProcessPlanChangeJob failed', [
            'plan_change_id' => $this->planChange->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
