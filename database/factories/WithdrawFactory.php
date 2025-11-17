<?php

namespace Database\Factories;

use App\Models\Gateway;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Withdraw>
 */
class WithdrawFactory extends Factory
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
            'external_id' => 'WD' . fake()->unique()->numerify('##########'),
            'status' => fake()->randomElement(['PENDING', 'PROCESSING', 'SUCCESS', 'DONE', 'FAILED', 'CANCELLED']),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'bank_account' => [
                'bank' => fake()->randomElement(['001', '237', '341', '104']),
                'agency' => fake()->numerify('####'),
                'account' => fake()->numerify('########-#'),
                'account_type' => fake()->randomElement(['checking', 'savings']),
                'account_holder_name' => fake()->name(),
                'account_holder_document' => fake()->numerify('###########'),
            ],
            'processed_at' => null,
        ];
    }

    /**
     * Set status to PENDING.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
            'processed_at' => null,
        ]);
    }

    /**
     * Set status to SUCCESS.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'SUCCESS',
            'processed_at' => now(),
        ]);
    }
}

