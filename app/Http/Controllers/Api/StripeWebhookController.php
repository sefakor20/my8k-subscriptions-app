<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionNewAccountJob;
use App\Mail\WelcomeNewCustomer;
use App\Models\User;
use App\Services\StripeWebhookHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeWebhookHandler $webhookHandler,
    ) {}

    /**
     * Handle incoming Stripe webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['type'] ?? '';

        Log::info('Stripe webhook received', [
            'event' => $event,
            'id' => $payload['id'] ?? null,
        ]);

        try {
            return match ($event) {
                'checkout.session.completed' => $this->handleCheckoutSessionCompleted($payload['data']['object'] ?? []),
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($payload['data']['object'] ?? []),
                'invoice.paid' => $this->handleInvoicePaid($payload['data']['object'] ?? []),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($payload['data']['object'] ?? []),
                'charge.refunded' => $this->handleChargeRefunded($payload['data']['object'] ?? []),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload['data']['object'] ?? []),
                default => $this->handleUnknownEvent($event),
            };
        } catch (Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle checkout.session.completed event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleCheckoutSessionCompleted(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleCheckoutSessionCompleted($data);

        if ($result['duplicate'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => 'Session already processed',
            ], 200);
        }

        if (($result['success'] ?? false) && isset($result['order_id'])) {
            // Dispatch provisioning job
            ProvisionNewAccountJob::dispatch(
                orderId: $result['order_id'],
                subscriptionId: $result['subscription_id'],
                planId: $result['plan_id'],
            );

            // Send welcome email if this is a new user
            if ($result['user_was_created'] ?? false) {
                $user = User::find($result['user_id']);

                if ($user instanceof User) {
                    $token = Password::createToken($user);
                    $passwordResetUrl = url("/reset-password/{$token}?email=" . urlencode($user->email));

                    Mail::to($user->email)->send(new WelcomeNewCustomer($user, $passwordResetUrl));

                    Log::info('Welcome email sent to new Stripe customer', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            }

            Log::info('Stripe checkout.session.completed processed, provisioning job dispatched', [
                'order_id' => $result['order_id'],
                'subscription_id' => $result['subscription_id'],
            ]);
        }

        $isSuccess = $result['success'] ?? false;

        return response()->json([
            'success' => $isSuccess,
            'message' => $result['message'] ?? 'Processed',
        ], $isSuccess ? 200 : 500);
    }

    /**
     * Handle payment_intent.succeeded event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentIntentSucceeded(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handlePaymentIntentSucceeded($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Logged',
        ], 200);
    }

    /**
     * Handle invoice.paid event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleInvoicePaid(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleInvoicePaid($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Logged',
        ], 200);
    }

    /**
     * Handle invoice.payment_failed event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleInvoicePaymentFailed(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleInvoicePaymentFailed($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Logged',
        ], 200);
    }

    /**
     * Handle charge.refunded event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleChargeRefunded(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleChargeRefunded($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Refund processed',
        ], 200);
    }

    /**
     * Handle customer.subscription.deleted event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionDeleted(array $data): JsonResponse
    {
        Log::info('Stripe subscription deleted', [
            'subscription_id' => $data['id'] ?? null,
            'customer' => $data['customer'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription deletion logged',
        ], 200);
    }

    /**
     * Handle unknown events.
     */
    private function handleUnknownEvent(string $event): JsonResponse
    {
        Log::info('Stripe webhook received for unhandled event', [
            'event' => $event,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event acknowledged',
        ], 200);
    }
}
