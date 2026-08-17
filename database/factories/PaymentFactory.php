<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_charge_id' => 'ch_'.fake()->unique()->bothify('????????????????'),
            'amount' => 49,
            'currency' => 'USD',
            'status' => 'succeeded',
            'type' => 'subscription',
            'description' => 'Subscription payment',
            'paid_at' => now(),
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }
}
