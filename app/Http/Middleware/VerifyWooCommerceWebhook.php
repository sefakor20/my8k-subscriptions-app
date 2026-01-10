<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWooCommerceWebhook
{
    /**
     * Handle an incoming request and verify WooCommerce webhook signature
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('services.woocommerce.webhook_secret');

        // Allow webhooks in local development without signature
        if (app()->environment('local') && empty($webhookSecret)) {
            Log::warning('WooCommerce webhook signature verification skipped in local environment');

            return $next($request);
        }

        // Verify webhook secret is configured
        if (empty($webhookSecret)) {
            Log::error('WooCommerce webhook secret not configured', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Webhook authentication not configured',
            ], 500);
        }

        // Get signature from header
        $signature = $request->header('X-WC-Webhook-Signature');

        if (empty($signature)) {
            Log::warning('WooCommerce webhook missing signature header', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'error' => 'Missing webhook signature',
            ], 401);
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $webhookSecret, true));

        // Verify signature matches
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('WooCommerce webhook signature verification failed', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'expected_signature' => $expectedSignature,
                'received_signature' => $signature,
            ]);

            return response()->json([
                'error' => 'Invalid webhook signature',
            ], 401);
        }

        // Signature verified successfully
        Log::info('WooCommerce webhook signature verified', [
            'url' => $request->fullUrl(),
            'event' => $request->header('X-WC-Webhook-Event'),
        ]);

        return $next($request);
    }
}
