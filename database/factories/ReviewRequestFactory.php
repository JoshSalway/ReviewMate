<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewRequest>
 */
class ReviewRequestFactory extends Factory
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
            'customer_id' => \App\Models\Customer::factory(),
            'status' => 'sent',
            'channel' => 'email',
            'sent_at' => now(),
            'opened_at' => null,
            'reviewed_at' => null,
        ];
    }

    public function opened(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'opened',
            'opened_at' => now(),
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
            'opened_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);
    }

    public function noResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_response',
        ]);
    }
}
