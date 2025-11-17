<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gateway>
 */
class GatewayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Gateway',
            'base_url' => fake()->url(),
            'type' => fake()->randomElement(['subadq_a', 'subadq_b']),
            'config' => [
                'timeout' => 30,
                'retry_attempts' => 3,
            ],
            'active' => true,
        ];
    }

    /**
     * Set gateway as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set gateway type to subadq_a.
     */
    public function subadqA(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'subadq_a',
            'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
        ]);
    }

    /**
     * Set gateway type to subadq_b.
     */
    public function subadqB(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'subadq_b',
            'base_url' => 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io',
        ]);
    }
}

