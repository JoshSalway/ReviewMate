<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['tradie', 'cafe', 'salon', 'healthcare', 'real_estate', 'retail', 'pet_services', 'fitness', 'other']),
            'google_place_id' => null,
            'owner_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'onboarding_completed_at' => null,
        ];
    }

    public function onboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_place_id' => 'ChIJ'.$this->faker->lexify('????????????????????????????????'),
            'onboarding_completed_at' => now(),
        ]);
    }
}
