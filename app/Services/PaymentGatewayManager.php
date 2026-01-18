<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Enums\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayContract> */
    private array $gateways = [];

    private ?PaymentGateway $defaultGateway = null;

    /**
     * Register a payment gateway.
     */
    public function register(PaymentGatewayContract $gateway): void
    {
        $this->gateways[$gateway->getIdentifier()->value] = $gateway;
    }

    /**
     * Set the default gateway.
     */
    public function setDefault(PaymentGateway $gateway): void
    {
        $this->defaultGateway = $gateway;
    }

    /**
     * Get a specific gateway by identifier.
     */
    public function gateway(PaymentGateway|string $identifier): PaymentGatewayContract
    {
        $key = $identifier instanceof PaymentGateway ? $identifier->value : $identifier;

        if (! isset($this->gateways[$key])) {
            throw new InvalidArgumentException("Payment gateway [{$key}] is not registered.");
        }

        return $this->gateways[$key];
    }

    /**
     * Get all available (configured and enabled) gateways.
     *
     * @return array<string, PaymentGatewayContract>
     */
    public function getAvailableGateways(): array
    {
        return array_filter(
            $this->gateways,
            fn(PaymentGatewayContract $gateway) => $gateway->isAvailable(),
        );
    }

    /**
     * Get all registered gateways.
     *
     * @return array<string, PaymentGatewayContract>
     */
    public function getAllGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Get the default gateway.
     */
    public function getDefaultGateway(): PaymentGatewayContract
    {
        if ($this->defaultGateway !== null && isset($this->gateways[$this->defaultGateway->value])) {
            return $this->gateways[$this->defaultGateway->value];
        }

        // Return first available gateway
        $available = $this->getAvailableGateways();

        if (empty($available)) {
            throw new InvalidArgumentException('No payment gateways are available.');
        }

        return reset($available);
    }

    /**
     * Check if a gateway is registered.
     */
    public function has(PaymentGateway|string $identifier): bool
    {
        $key = $identifier instanceof PaymentGateway ? $identifier->value : $identifier;

        return isset($this->gateways[$key]);
    }

    /**
     * Check if a gateway is available (registered and configured).
     */
    public function isAvailable(PaymentGateway|string $identifier): bool
    {
        if (! $this->has($identifier)) {
            return false;
        }

        return $this->gateway($identifier)->isAvailable();
    }

    /**
     * Get available direct gateways (excluding WooCommerce).
     *
     * @return array<string, PaymentGatewayContract>
     */
    public function getDirectGateways(): array
    {
        return array_filter(
            $this->getAvailableGateways(),
            fn(PaymentGatewayContract $gateway) => $gateway->getIdentifier()->isDirectGateway(),
        );
    }
}
