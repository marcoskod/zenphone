<?php

namespace Database\Factories;

use App\Models\Topup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topup>
 */
class TopupFactory extends Factory
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
            'amount_fcfa' => fake()->randomElement([1000, 2500, 5000, 10000]),
            'operator' => fake()->randomElement(['Orange Money', 'Wave', 'MTN MoMo', 'Moov Money']),
            'phone_number' => '+' . fake()->numerify('###########'),
            'status' => 'pending',
            'external_reference' => null,
        ];
    }
}
