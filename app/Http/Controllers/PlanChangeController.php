<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Enums\PlanChangeStatus;
use App\Models\PlanChange;
use App\Services\PaymentGatewayManager;
use App\Services\PlanChangeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PlanChangeController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private PlanChangeService $planChangeService,
    ) {}

    /**
     * Handle gateway callback/return URL for plan change payments.
     */
    public function callback(Request $request, string $gateway): View|RedirectResponse
    {
        $gatewayEnum = PaymentGateway::tryFrom($gateway);

        if (! $gatewayEnum) {
            abort(404, 'Invalid payment gateway');
        }

        // Get reference from query parameters
        $reference = match ($gateway) {
            'paystack' => $request->query('reference') ?? $request->query('trxref'),
            'stripe' => $request->query('session_id'),
            default => null,
        };

        if (! $reference) {
            return redirect()->route('dashboard')
                ->with('error', 'Payment reference not found');
        }

        // Find the plan change by reference
        $planChange = PlanChange::where('payment_reference', $reference)->first();

        if (! $planChange) {
            Log::warning('Plan change not found for reference', [
                'gateway' => $gateway,
                'reference' => $reference,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Plan change not found');
        }

        // Check if already processed
        if ($planChange->status === PlanChangeStatus::Completed) {
            return redirect()->route('dashboard')
                ->with('success', 'Your plan has already been changed successfully!');
        }

        if ($planChange->status === PlanChangeStatus::Failed) {
            return redirect()->route('dashboard')
                ->with('error', 'This plan change payment has already failed. Please try again.');
        }

        try {
            $gatewayInstance = $this->gatewayManager->gateway($gateway);
            $result = $gatewayInstance->verifyPayment($reference);

            if ($result['success']) {
                // Handle successful payment
                $this->planChangeService->handlePaymentSuccess(
                    $planChange,
                    $result['data'] ?? [],
                );

                return redirect()->route('dashboard')
                    ->with('success', 'Your plan has been changed successfully!');
            } else {
                // Handle failed payment
                $this->planChangeService->handlePaymentFailure(
                    $planChange,
                    $result['error'] ?? 'Payment verification failed',
                );

                return redirect()->route('dashboard')
                    ->with('error', 'Payment failed. Your plan has not been changed.');
            }
        } catch (Exception $e) {
            Log::error('Plan change payment callback verification failed', [
                'gateway' => $gateway,
                'reference' => $reference,
                'plan_change_id' => $planChange->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Unable to verify payment status. Please contact support.');
        }
    }

    /**
     * Cancel a pending or scheduled plan change.
     */
    public function cancel(Request $request, PlanChange $planChange): RedirectResponse
    {
        // Verify ownership
        if ($planChange->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $planChange->canBeCancelled()) {
            return back()->with('error', 'This plan change cannot be cancelled.');
        }

        if ($this->planChangeService->cancelChange($planChange)) {
            return back()->with('success', 'Plan change has been cancelled.');
        }

        return back()->with('error', 'Unable to cancel plan change. Please try again.');
    }
}
