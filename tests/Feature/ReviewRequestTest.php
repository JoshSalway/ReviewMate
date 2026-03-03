<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\ReviewRequest;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access requests page', function () {
    auth()->logout();
    $this->get('/requests')->assertRedirect('/login');
});

test('users can view their review requests', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    ReviewRequest::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
    ]);

    $this->get('/requests')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('requests/index')
            ->has('stats')
            ->has('requests.data', 3)
        );
});

test('users can send a review request to a customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->post('/requests', [
        'customer_id' => $customer->id,
        'channel' => 'email',
    ])->assertRedirect();

    $this->assertDatabaseHas('review_requests', [
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
        'status' => 'sent',
        'channel' => 'email',
    ]);
});

test('cannot send request to customer from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $customer = Customer::factory()->create(['business_id' => $otherBusiness->id]);

    $this->post('/requests', [
        'customer_id' => $customer->id,
        'channel' => 'email',
    ])->assertStatus(404);
});

test('review requests stats are scoped to business', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    ReviewRequest::factory()->reviewed()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
    ]);

    $otherBusiness = Business::factory()->onboarded()->create();
    $otherCustomer = Customer::factory()->create(['business_id' => $otherBusiness->id]);
    ReviewRequest::factory()->count(5)->create([
        'business_id' => $otherBusiness->id,
        'customer_id' => $otherCustomer->id,
    ]);

    $this->get('/requests')
        ->assertInertia(fn ($page) => $page
            ->where('stats.sent', 2)
            ->where('stats.reviewed', 2)
        );
});
