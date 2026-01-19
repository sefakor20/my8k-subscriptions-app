<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Enums\PaymentTransactionStatus;
use App\Jobs\ProvisionNewAccountJob;
use App\Mail\WelcomeNewCustomer;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentGatewayManager;
use App\Services\SubscriptionOrderService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $gatewayManager,
        private SubscriptionOrderService $subscriptionService,
        private InvoiceService $invoiceService,
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
                // Create subscription and order (idempotent)
                $creationResult = $this->createSubscriptionFromCallback(
                    $gatewayEnum,
                    $reference,
                    $result['data'] ?? [],
                );

                return view('checkout.success', [
                    'reference' => $reference,
                    'gateway' => $gateway,
                    'subscription' => $creationResult['subscription'] ?? null,
                    'order' => $creationResult['order'] ?? null,
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

    /**
     * Create subscription from callback verification data.
     *
     * @param  array<string, mixed>  $verificationData
     * @return array<string, mixed>
     */
    private function createSubscriptionFromCallback(
        PaymentGateway $gateway,
        string $reference,
        array $verificationData,
    ): array {
        // Extract email and plan from verification data or pending transaction
        $transaction = PaymentTransaction::where('reference', $reference)->first();

        // Get email from verification data or transaction user
        $email = $this->extractEmail($gateway, $verificationData, $transaction);

        // Get plan from verification data metadata or transaction
        $plan = $this->extractPlan($gateway, $verificationData, $transaction);

        if (! $email || ! $plan) {
            Log::warning('Cannot create subscription from callback - missing data', [
                'gateway' => $gateway->value,
                'reference' => $reference,
                'has_email' => (bool) $email,
                'has_plan' => (bool) $plan,
            ]);

            return ['success' => false, 'message' => 'Missing required data'];
        }

        // Prepare payment data for order creation
        $paymentData = $this->preparePaymentData($gateway, $verificationData);

        $result = $this->subscriptionService->createSubscriptionAndOrder(
            $gateway,
            $reference,
            $email,
            $plan,
            $paymentData,
        );

        // Dispatch provisioning job if newly created (not duplicate)
        if ($result['success'] && ! $result['duplicate']) {
            ProvisionNewAccountJob::dispatch(
                orderId: $result['order']->id,
                subscriptionId: $result['subscription']->id,
                planId: $plan->id,
            );

            // Generate and send invoice
            try {
                $this->invoiceService->processOrderInvoice($result['order']);
            } catch (Exception $e) {
                Log::error('Failed to process invoice for checkout', [
                    'order_id' => $result['order']->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send welcome email if new user
            if ($result['user_was_created'] ?? false) {
                $this->sendWelcomeEmail($result['user']);
            }
        }

        return $result;
    }

    /**
     * Extract email from verification data.
     *
     * @param  array<string, mixed>  $data
     */
    private function extractEmail(PaymentGateway $gateway, array $data, ?PaymentTransaction $transaction): ?string
    {
        return match ($gateway) {
            PaymentGateway::Paystack => $data['customer']['email'] ?? $transaction?->user?->email,
            PaymentGateway::Stripe => $data['customer_email'] ?? $data['customer_details']['email'] ?? $transaction?->user?->email,
            default => $transaction?->user?->email,
        };
    }

    /**
     * Extract plan from verification data.
     *
     * @param  array<string, mixed>  $data
     */
    private function extractPlan(PaymentGateway $gateway, array $data, ?PaymentTransaction $transaction): ?Plan
    {
        $planId = match ($gateway) {
            PaymentGateway::Paystack => $data['metadata']['plan_id'] ?? null,
            PaymentGateway::Stripe => $data['metadata']['plan_id'] ?? null,
            default => null,
        };

        // Fallback to transaction metadata if available
        if (! $planId && $transaction) {
            $planId = $transaction->gateway_response['metadata']['plan_id'] ?? null;
        }

        return $planId ? Plan::find($planId) : null;
    }

    /**
     * Prepare payment data for order creation.
     *
     * @param  array<string, mixed>  $verificationData
     * @return array<string, mixed>
     */
    private function preparePaymentData(PaymentGateway $gateway, array $verificationData): array
    {
        return match ($gateway) {
            PaymentGateway::Paystack => [
                'amount' => $verificationData['amount'] ?? 0,
                'currency' => $verificationData['currency'] ?? 'GHS',
                'channel' => $verificationData['channel'] ?? 'card',
                'customer' => $verificationData['customer'] ?? [],
                'authorization' => $verificationData['authorization'] ?? null,
                'metadata' => $verificationData['metadata'] ?? [],
            ],
            PaymentGateway::Stripe => [
                'amount_total' => $verificationData['amount_total'] ?? 0,
                'currency' => $verificationData['currency'] ?? 'usd',
                'payment_intent' => $verificationData['payment_intent'] ?? null,
                'customer' => $verificationData['customer'] ?? null,
                'customer_details' => $verificationData['customer_details'] ?? [],
                'customer_email' => $verificationData['customer_email'] ?? null,
                'metadata' => $verificationData['metadata'] ?? [],
            ],
            default => $verificationData,
        };
    }

    /**
     * Send welcome email to new user.
     */
    private function sendWelcomeEmail(User $user): void
    {
        try {
            $token = Password::createToken($user);
            $passwordResetUrl = url("/reset-password/{$token}?email=" . urlencode($user->email));

            Mail::to($user->email)->send(new WelcomeNewCustomer($user, $passwordResetUrl));
        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
