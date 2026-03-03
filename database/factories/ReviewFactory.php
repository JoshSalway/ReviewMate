<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => \App\Models\Business::factory(),
            'customer_id' => null,
            'review_request_id' => null,
            'rating' => $this->faker->numberBetween(3, 5),
            'body' => $this->faker->paragraph(),
            'reviewer_name' => $this->faker->name(),
            'source' => 'google',
            'reviewed_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
