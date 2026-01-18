<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
    ) {}

    /**
     * Display the checkout page with plan selection.
     */
    public function index(): View
    {
        $plans = Plan::where('is_active', true)->get();
        $gateways = $this->gatewayManager->getDirectGateways();

        return view('checkout.index', [
            'plans' => $plans,
            'gateways' => $gateways,
        ]);
    }

    /**
     * Display gateway selection for a specific plan.
     */
    public function selectGateway(Plan $plan): View
    {
        if (! $plan->is_active) {
            abort(404, 'Plan not available');
        }

        $gateways = $this->gatewayManager->getDirectGateways();

        return view('checkout.gateway', [
            'plan' => $plan,
            'gateways' => $gateways,
        ]);
    }

    /**
     * Initiate checkout with selected gateway.
     */
    public function initiate(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'gateway' => 'required|string|in:paystack,stripe',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = $request->user();
        $gatewayId = $request->gateway;

        if (! $plan->is_active) {
            return back()->with('error', 'Selected plan is not available');
        }

        try {
            $gateway = $this->gatewayManager->gateway($gatewayId);

            if (! $gateway->isAvailable()) {
                return back()->with('error', 'Selected payment method is not available');
            }

            // Initiate payment
            $result = $gateway->initiatePayment($user, $plan);

            // Create pending transaction record
            PaymentTransaction::create([
                'user_id' => $user->id,
                'payment_gateway' => PaymentGateway::from($gatewayId),
                'reference' => $result['reference'],
                'status' => PaymentTransactionStatus::Pending,
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'USD',
            ]);

            Log::info('Checkout initiated', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $gatewayId,
                'reference' => $result['reference'],
            ]);

            // Redirect to gateway checkout
            return redirect()->away($result['checkout_url']);
        } catch (Exception $e) {
            Log::error('Checkout initiation failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $gatewayId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to initiate checkout. Please try again.');
        }
    }

    /**
     * Handle gateway callback/return URL.
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
            return redirect()->route('checkout.index')
                ->with('error', 'Payment reference not found');
        }

        try {
            $gatewayInstance = $this->gatewayManager->gateway($gateway);
            $result = $gatewayInstance->verifyPayment($reference);

            if ($result['success']) {
                return view('checkout.success', [
                    'reference' => $reference,
                    'gateway' => $gateway,
                ]);
            } else {
                return view('checkout.failed', [
                    'reference' => $reference,
                    'gateway' => $gateway,
                    'error' => $result['error'] ?? 'Payment verification failed',
                ]);
            }
        } catch (Exception $e) {
            Log::error('Payment callback verification failed', [
                'gateway' => $gateway,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return view('checkout.failed', [
                'reference' => $reference,
                'gateway' => $gateway,
                'error' => 'Unable to verify payment status',
            ]);
        }
    }

    /**
     * Display checkout success page.
     */
    public function success(): View
    {
        return view('checkout.success');
    }

    /**
     * Display checkout cancellation page.
     */
    public function cancel(): View
    {
        return view('checkout.cancel');
    }

    /**
     * Verify payment status (for polling).
     */
    public function verify(Request $request, string $reference): \Illuminate\Http\JsonResponse
    {
        $transaction = PaymentTransaction::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status->value,
            'is_complete' => $transaction->status->isTerminal(),
            'is_successful' => $transaction->isSuccessful(),
        ]);
    }
}
