<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentGateway: string
{
    case WooCommerce = 'woocommerce';
    case Paystack = 'paystack';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::WooCommerce => 'WooCommerce',
            self::Paystack => 'Paystack',
            self::Stripe => 'Stripe',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WooCommerce => 'purple',
            self::Paystack => 'cyan',
            self::Stripe => 'indigo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WooCommerce => 'shopping-cart',
            self::Paystack => 'credit-card',
            self::Stripe => 'credit-card',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WooCommerce => 'Pay via WooCommerce checkout',
            self::Paystack => 'Pay with card, bank transfer, or mobile money',
            self::Stripe => 'Pay with card (Visa, Mastercard, etc.)',
        };
    }

    public function isDirectGateway(): bool
    {
        return $this !== self::WooCommerce;
    }
}
