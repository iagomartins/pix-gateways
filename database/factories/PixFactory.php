<?php

namespace Database\Factories;

use App\Models\Gateway;
use App\Models\Pix;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pix>
 */
class PixFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gateway_id' => Gateway::factory(),
            'external_id' => 'PIX' . fake()->unique()->numerify('##########'),
            'status' => fake()->randomElement(['PENDING', 'PROCESSING', 'CONFIRMED', 'PAID', 'CANCELLED', 'FAILED']),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'payer_name' => fake()->name(),
            'payer_cpf' => fake()->numerify('###########'),
            'qr_code' => fake()->text(100),
            'paid_at' => null,
        ];
    }

    /**
     * Set status to PENDING.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
            'paid_at' => null,
        ]);
    }

    /**
     * Set status to PAID.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PAID',
            'paid_at' => now(),
        ]);
    }
}

