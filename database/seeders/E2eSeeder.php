<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        // Clean slate
        ReviewRequest::query()->delete();
        Review::query()->delete();
        Customer::query()->delete();
        EmailTemplate::query()->delete();
        Business::query()->delete();
        User::query()->delete();

        $user = User::create([
            'name' => 'E2E Test User',
            'email' => 'e2e@reviewmate.test',
            'workos_id' => 'user_e2e_test_fixed_id_001',
            'avatar' => '',
            'role' => 'user',
            'is_admin' => true, // admin so no free plan limits block tests
        ]);

        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'E2E Test Business',
            'type' => 'tradie',
            'owner_name' => 'E2E Owner',
            'onboarding_completed_at' => now(),
        ]);

        $customer1 = Customer::create([
            'business_id' => $business->id,
            'name' => 'Alice Smith',
            'email' => 'alice@example.test',
            'phone' => '+61400000001',
        ]);

        $customer2 = Customer::create([
            'business_id' => $business->id,
            'name' => 'Bob Jones',
            'email' => 'bob@example.test',
            'phone' => '+61400000002',
        ]);

        $customer3 = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carol White',
            'email' => 'carol@example.test',
            'phone' => '+61400000003',
        ]);

        // 2 sent review requests (older than 30 days so we can send new ones in tests)
        $req1 = ReviewRequest::create([
            'business_id' => $business->id,
            'customer_id' => $customer1->id,
            'status' => 'reviewed',
            'channel' => 'email',
            'sent_at' => now()->subDays(45),
            'reviewed_at' => now()->subDays(40),
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(40),
        ]);

        $req2 = ReviewRequest::create([
            'business_id' => $business->id,
            'customer_id' => $customer2->id,
            'status' => 'opened',
            'channel' => 'email',
            'sent_at' => now()->subDays(45),
            'opened_at' => now()->subDays(44),
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(44),
        ]);

        // Email templates (same as what onboarding creates for 'tradie' type)
        EmailTemplate::create([
            'business_id' => $business->id,
            'type' => 'request',
            'subject' => 'How was the job, {customer_name}?',
            'body' => "Hi {customer_name},\n\nThanks for having us out! We really enjoyed working on your job and hope everything is looking great.\n\nWould you mind leaving us a quick Google review? It takes less than 60 seconds and really helps our business:\n\n{review_link}\n\nCheers,\n{owner_name}\n{business_name}",
        ]);

        EmailTemplate::create([
            'business_id' => $business->id,
            'type' => 'follow_up',
            'subject' => 'Just checking in — how did we do?',
            'body' => "Hi {customer_name},\n\nWe sent you a review request a few days ago and just wanted to follow up.\n\nYour feedback means the world to us — it only takes a minute:\n\n{review_link}\n\nThanks again for your business!\n{owner_name}",
        ]);

        EmailTemplate::create([
            'business_id' => $business->id,
            'type' => 'sms',
            'subject' => null,
            'body' => 'Hi {customer_name}, thanks for choosing {business_name}! Got a minute to leave a review? It really helps: {review_link}',
        ]);

        // 1 review with 5 stars
        Review::create([
            'business_id' => $business->id,
            'customer_id' => $customer1->id,
            'review_request_id' => $req1->id,
            'rating' => 5,
            'body' => 'Excellent service! Highly recommend.',
            'reviewer_name' => 'Alice Smith',
            'source' => 'google',
            'reviewed_at' => now()->subDays(40),
        ]);
    }
}
