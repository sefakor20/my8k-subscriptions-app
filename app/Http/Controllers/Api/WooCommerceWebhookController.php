<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ExtendAccountJob;
use App\Jobs\ProvisionNewAccountJob;
use App\Jobs\SuspendAccountJob;
use App\Mail\PaymentFailureReminder;
use App\Mail\WelcomeNewCustomer;
use App\Models\User;
use App\Services\WooCommerceWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Exception;

class WooCommerceWebhookController extends Controller
{
    public function __construct(
        private WooCommerceWebhookHandler $webhookHandler,
    ) {}

    /**
     * Handle WooCommerce order completed webhook
     */
    public function orderCompleted(Request $request): JsonResponse
    {
        try {
            $orderData = $request->all();

            Log::info('WooCommerce order completed webhook received', [
                'order_id' => $orderData['id'] ?? null,
            ]);

            $result = $this->webhookHandler->processOrderCompleted($orderData);

            if ($result['duplicate'] ?? false) {
                Log::info('Duplicate order webhook, skipping', [
                    'order_id' => $orderData['id'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order already processed',
                ], 200);
            }

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
                    // Generate password reset token
                    $token = Password::createToken($user);
                    $passwordResetUrl = url("/reset-password/{$token}?email=" . urlencode($user->email));

                    // Send welcome email
                    Mail::to($user->email)->send(new WelcomeNewCustomer($user, $passwordResetUrl));

                    Log::info('Welcome email sent to new customer', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            }

            Log::info('Order processed and provisioning job dispatched', [
                'order_id' => $result['order_id'],
                'subscription_id' => $result['subscription_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order processed successfully',
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to process order completed webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process order',
            ], 500);
        }
    }

    /**
     * Handle WooCommerce subscription renewed webhook
     */
    public function subscriptionRenewed(Request $request): JsonResponse
    {
        try {
            $subscriptionData = $request->all();

            Log::info('WooCommerce subscription renewed webhook received', [
                'subscription_id' => $subscriptionData['id'] ?? null,
            ]);

            $woocommerceSubscriptionId = (string) $subscriptionData['id'];

            // Find subscription by WooCommerce ID
            $subscription = $this->webhookHandler->findSubscriptionByWooCommerceId($woocommerceSubscriptionId);

            if (!$subscription instanceof \App\Models\Subscription) {
                Log::warning('Subscription not found for renewal webhook', [
                    'woocommerce_subscription_id' => $woocommerceSubscriptionId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Subscription not found',
                ], 404);
            }

            if (! $subscription->service_account_id) {
                Log::warning('No service account linked to subscription', [
                    'subscription_id' => $subscription->id,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'No service account linked',
                ], 400);
            }

            // Clear any payment failure tracking after successful renewal
            if ($subscription->hasPaymentFailure()) {
                $subscription->clearPaymentFailure();
                Log::info('Cleared payment failure after successful WooCommerce renewal', [
                    'subscription_id' => $subscription->id,
                ]);
            }

            // Dispatch extension job
            ExtendAccountJob::dispatch(
                subscriptionId: $subscription->id,
                serviceAccountId: $subscription->service_account_id,
                durationDays: $subscription->plan->duration_days,
            );

            Log::info('Subscription renewal job dispatched', [
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Renewal processed successfully',
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to process subscription renewal webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process renewal',
            ], 500);
        }
    }

    /**
     * Handle WooCommerce subscription cancelled webhook
     */
    public function subscriptionCancelled(Request $request): JsonResponse
    {
        try {
            $subscriptionData = $request->all();

            Log::info('WooCommerce subscription cancelled webhook received', [
                'subscription_id' => $subscriptionData['id'] ?? null,
            ]);

            $woocommerceSubscriptionId = (string) $subscriptionData['id'];

            // Find subscription by WooCommerce ID
            $subscription = $this->webhookHandler->findSubscriptionByWooCommerceId($woocommerceSubscriptionId);

            if (!$subscription instanceof \App\Models\Subscription) {
                Log::warning('Subscription not found for cancellation webhook', [
                    'woocommerce_subscription_id' => $woocommerceSubscriptionId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Subscription not found',
                ], 404);
            }

            // Update subscription status to Cancelled
            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'auto_renew' => false,
            ]);

            // If service account exists, suspend it
            if ($subscription->service_account_id) {
                SuspendAccountJob::dispatch(
                    subscriptionId: $subscription->id,
                    serviceAccountId: $subscription->service_account_id,
                );

                Log::info('Subscription cancelled and suspend job dispatched', [
                    'subscription_id' => $subscription->id,
                ]);
            } else {
                Log::info('Subscription cancelled, no service account to suspend', [
                    'subscription_id' => $subscription->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cancellation processed successfully',
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to process subscription cancellation webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process cancellation',
            ], 500);
        }
    }

    /**
     * Handle WooCommerce payment failed webhook
     */
    public function paymentFailed(Request $request): JsonResponse
    {
        try {
            $subscriptionData = $request->all();

            Log::warning('WooCommerce payment failed webhook received', [
                'subscription_id' => $subscriptionData['id'] ?? null,
                'customer_email' => $subscriptionData['billing']['email'] ?? null,
            ]);

            $woocommerceSubscriptionId = (string) $subscriptionData['id'];

            // Find subscription by WooCommerce ID
            $subscription = $this->webhookHandler->findSubscriptionByWooCommerceId($woocommerceSubscriptionId);

            if (!$subscription instanceof \App\Models\Subscription) {
                Log::warning('Subscription not found for payment failed webhook', [
                    'woocommerce_subscription_id' => $woocommerceSubscriptionId,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Subscription not found',
                ], 404);
            }

            // Extract payment method info
            $paymentMethod = $subscriptionData['payment_method_title'] ?? 'Unknown payment method';

            // Record the payment failure on the subscription
            $subscription->recordPaymentFailure();

            // Log payment failure for admin review
            Log::critical('Payment failed for subscription, manual review required', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'woocommerce_subscription_id' => $woocommerceSubscriptionId,
                'customer_email' => $subscriptionData['billing']['email'] ?? null,
                'payment_method' => $paymentMethod,
                'failure_count' => $subscription->payment_failure_count,
            ]);

            // Send payment failure email to customer
            Mail::to($subscription->user->email)
                ->send(new PaymentFailureReminder($subscription, $paymentMethod));

            Log::info('Payment failure reminder email sent to customer', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'email' => $subscription->user->email,
            ]);

            // Note: Subscription status update and service suspension are handled by
            // SuspendFailedPaymentsCommand after grace period expires

            return response()->json([
                'success' => true,
                'message' => 'Payment failure processed and customer notified',
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to process payment failed webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process payment failure',
            ], 500);
        }
    }
}
