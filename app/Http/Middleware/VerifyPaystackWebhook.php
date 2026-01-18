<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaystackWebhook
{
    /**
     * Handle an incoming request and verify Paystack webhook signature.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('services.paystack.webhook_secret');

        // Allow webhooks in local development without signature
        if (app()->environment('local') && empty($webhookSecret)) {
            Log::warning('Paystack webhook signature verification skipped in local environment');

            return $next($request);
        }

        // Verify webhook secret is configured
        if (empty($webhookSecret)) {
            Log::error('Paystack webhook secret not configured', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Webhook authentication not configured',
            ], 500);
        }

        // Get signature from header
        $signature = $request->header('X-Paystack-Signature');

        if (empty($signature)) {
            Log::warning('Paystack webhook missing signature header', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json([
                'error' => 'Missing webhook signature',
            ], 401);
        }

        // Calculate expected signature (Paystack uses HMAC SHA512)
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $payload, $webhookSecret);

        // Verify signature matches
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Paystack webhook signature verification failed', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Invalid webhook signature',
            ], 401);
        }

        // Signature verified successfully
        Log::info('Paystack webhook signature verified', [
            'url' => $request->fullUrl(),
            'event' => $request->input('event'),
        ]);

        return $next($request);
    }
}
