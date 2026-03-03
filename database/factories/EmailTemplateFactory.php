<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
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
            'type' => 'request',
            'subject' => 'How was your experience with {business_name}?',
            'body' => "Hi {customer_name},\n\nThank you for choosing {business_name}. We'd love to hear about your experience!\n\nWould you mind taking 60 seconds to leave us a review?\n\n{review_link}\n\nThanks,\n{owner_name}",
        ];
    }
}
