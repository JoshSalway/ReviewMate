<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
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
