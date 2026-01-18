<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\PaymentGateway;
use App\Services\PaymentGatewayManager;
use App\Services\PaymentGateways\PaystackGateway;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaystackApiClient;
use App\Services\StripeApiClient;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register API clients as singletons
        $this->app->singleton(PaystackApiClient::class, function ($app) {
            return new PaystackApiClient(
                secretKey: config('services.paystack.secret_key'),
                baseUrl: config('services.paystack.base_url', 'https://api.paystack.co'),
            );
        });

        $this->app->singleton(StripeApiClient::class, function ($app) {
            return new StripeApiClient(
                secretKey: config('services.stripe.secret_key'),
            );
        });

        // Register gateway implementations
        $this->app->singleton(PaystackGateway::class, function ($app) {
            return new PaystackGateway($app->make(PaystackApiClient::class));
        });

        $this->app->singleton(StripeGateway::class, function ($app) {
            return new StripeGateway($app->make(StripeApiClient::class));
        });

        // Register the gateway manager
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager();

            // Register all gateways
            $manager->register($app->make(PaystackGateway::class));
            $manager->register($app->make(StripeGateway::class));

            // Set default gateway based on config or availability
            $defaultGateway = config('services.default_payment_gateway', 'paystack');

            if ($defaultGateway === 'paystack') {
                $manager->setDefault(PaymentGateway::Paystack);
            } elseif ($defaultGateway === 'stripe') {
                $manager->setDefault(PaymentGateway::Stripe);
            }

            return $manager;
        });

        // Alias for easier access
        $this->app->alias(PaymentGatewayManager::class, 'payment.gateway');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
