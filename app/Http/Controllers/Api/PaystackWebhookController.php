<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionNewAccountJob;
use App\Mail\WelcomeNewCustomer;
use App\Models\User;
use App\Services\PaystackWebhookHandler;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private PaystackWebhookHandler $webhookHandler,
    ) {}

    /**
     * Handle incoming Paystack webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $payload['data']['reference'] ?? null,
        ]);

        try {
            return match ($event) {
                'charge.success' => $this->handleChargeSuccess($payload['data'] ?? []),
                'subscription.create' => $this->handleSubscriptionCreate($payload['data'] ?? []),
                'subscription.not_renew' => $this->handleSubscriptionNotRenew($payload['data'] ?? []),
                'subscription.disable' => $this->handleSubscriptionDisable($payload['data'] ?? []),
                'charge.failed' => $this->handleChargeFailed($payload['data'] ?? []),
                'refund.processed' => $this->handleRefundProcessed($payload['data'] ?? []),
                default => $this->handleUnknownEvent($event),
            };
        } catch (Exception $e) {
            Log::error('Paystack webhook processing failed', [
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
     * Handle charge.success event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleChargeSuccess(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleChargeSuccess($data);

        if ($result['duplicate'] ?? false) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction already processed',
            ], 200);
        }

        if ($result['success'] ?? false) {
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

                    Log::info('Welcome email sent to new Paystack customer', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            }

            Log::info('Paystack charge.success processed, provisioning job dispatched', [
                'order_id' => $result['order_id'],
                'subscription_id' => $result['subscription_id'],
            ]);
        }

        return response()->json([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Processed',
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Handle subscription.create event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionCreate(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleSubscriptionCreate($data);

        return response()->json([
            'success' => $result['success'] ?? true,
            'message' => $result['message'] ?? 'Subscription creation logged',
        ], 200);
    }

    /**
     * Handle subscription.not_renew event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionNotRenew(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleSubscriptionNotRenew($data);

        return response()->json([
            'success' => $result['success'] ?? true,
            'message' => $result['message'] ?? 'Processed',
        ], 200);
    }

    /**
     * Handle subscription.disable event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionDisable(array $data): JsonResponse
    {
        // Similar to subscription.not_renew
        $result = $this->webhookHandler->handleSubscriptionNotRenew($data);

        return response()->json([
            'success' => $result['success'] ?? true,
            'message' => 'Subscription disabled',
        ], 200);
    }

    /**
     * Handle charge.failed event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleChargeFailed(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleChargeFailed($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Charge failure logged',
        ], 200);
    }

    /**
     * Handle refund.processed event.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleRefundProcessed(array $data): JsonResponse
    {
        $result = $this->webhookHandler->handleRefundProcessed($data);

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Refund processed',
        ], 200);
    }

    /**
     * Handle unknown events.
     */
    private function handleUnknownEvent(string $event): JsonResponse
    {
        Log::info('Paystack webhook received for unhandled event', [
            'event' => $event,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event acknowledged',
        ], 200);
    }
}
