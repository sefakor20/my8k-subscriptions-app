<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeWebhook
{
    /**
     * Handle an incoming request and verify Stripe webhook signature.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        // Allow webhooks in local development without signature
        if (app()->environment('local') && empty($webhookSecret)) {
            Log::warning('Stripe webhook signature verification skipped in local environment');

            return $next($request);
        }

        // Verify webhook secret is configured
        if (empty($webhookSecret)) {
            Log::error('Stripe webhook secret not configured', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Webhook authentication not configured',
            ], 500);
        }

        // Get signature from header
        $signature = $request->header('Stripe-Signature');

        if (empty($signature)) {
            Log::warning('Stripe webhook missing signature header', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'error' => 'Missing webhook signature',
            ], 401);
        }

        // Verify signature using Stripe SDK
        $payload = $request->getContent();

        try {
            Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook signature',
            ], 401);
        }

        // Signature verified successfully
        Log::info('Stripe webhook signature verified', [
            'url' => $request->fullUrl(),
            'event_type' => $request->input('type'),
        ]);

        return $next($request);
    }
}
