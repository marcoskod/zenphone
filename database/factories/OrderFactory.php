<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'fivesim_order_id' => fake()->unique()->numberBetween(100000, 999999),
            'service' => fake()->randomElement(['whatsapp', 'google', 'instagram', 'telegram']),
            'country' => fake()->randomElement(['russia', 'usa', 'ivory_coast', 'senegal']),
            'phone' => '+' . fake()->numerify('###########'),
            'price_fcfa' => fake()->randomFloat(2, 200, 800),
            'status' => 'pending',
            'sms_code' => null,
        ];
    }
}
