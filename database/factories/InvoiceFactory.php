<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::factory()->create();
        $subtotal = fake()->randomFloat(2, 10, 200);
        $taxAmount = 0;
        $total = $subtotal + $taxAmount;

        return [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'invoice_number' => 'INV-' . now()->year . '-' . mb_str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => InvoiceStatus::Paid,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $order->currency ?? 'USD',
            'line_items' => [
                [
                    'description' => $order->subscription?->plan?->name ?? 'Subscription Plan',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'amount' => $subtotal,
                ],
            ],
            'customer_details' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'company_details' => [
                'name' => config('invoice.company.name', 'My8K IPTV'),
                'email' => config('invoice.company.email', ''),
                'address' => config('invoice.company.address', ''),
            ],
            'issued_at' => now(),
            'paid_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::Draft,
            'paid_at' => null,
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::Issued,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function void(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::Void,
            'voided_at' => now(),
            'void_reason' => 'Voided for testing',
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'currency' => $order->currency,
            'subtotal' => $order->amount,
            'total' => $order->amount,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
            'customer_details' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
