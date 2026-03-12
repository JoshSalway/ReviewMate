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

test('guests cannot access customers page', function () {
    auth()->logout();
    $this->get('/customers')->assertRedirect('/login');
});

test('users can view their customers list', function () {
    Customer::factory()->count(3)->create(['business_id' => $this->business->id]);

    $this->get('/customers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customers/index')
            ->has('customers.data', 3)
        );
});

test('users can add a new customer', function () {
    $this->post('/customers', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '0400000000',
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'business_id' => $this->business->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

test('customer name is required', function () {
    $this->post('/customers', ['email' => 'jane@example.com'])
        ->assertSessionHasErrors('name');
});

test('users can delete their own customers', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->delete("/customers/{$customer->id}")
        ->assertRedirect();

    $this->assertModelMissing($customer);
});

test('users cannot delete customers from other businesses', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $customer = Customer::factory()->create(['business_id' => $otherBusiness->id]);

    $this->delete("/customers/{$customer->id}")
        ->assertForbidden();
});

test('users cannot see other businesses customers', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    Customer::factory()->count(3)->create(['business_id' => $otherBusiness->id]);
    Customer::factory()->count(2)->create(['business_id' => $this->business->id]);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 2)
        );
});

test('customers index returns filters prop', function () {
    $this->get('/customers?search=jane&status=reviewed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('filters')
            ->where('filters.search', 'jane')
            ->where('filters.status', 'reviewed')
        );
});

test('search filter returns only matching customers by name', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Bob Jones', 'email' => 'bob@example.com']);

    $this->get('/customers?search=jane')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Jane Smith')
        );
});

test('search filter returns matching customers by email', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Alice Brown', 'email' => 'alice@example.com']);
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Bob Jones', 'email' => 'bob@example.com']);

    $this->get('/customers?search=alice%40example.com')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.email', 'alice@example.com')
        );
});

test('status filter returns only customers with no request', function () {
    $customerWithRequest = Customer::factory()->create(['business_id' => $this->business->id]);
    $customerWithoutRequest = Customer::factory()->create(['business_id' => $this->business->id]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customerWithRequest->id,
        'status' => 'sent',
    ]);

    $this->get('/customers?status=no_request')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $customerWithoutRequest->id)
        );
});

test('status filter returns only reviewed customers', function () {
    $reviewedCustomer = Customer::factory()->create(['business_id' => $this->business->id]);
    $sentCustomer = Customer::factory()->create(['business_id' => $this->business->id]);

    ReviewRequest::factory()->reviewed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $reviewedCustomer->id,
    ]);

    ReviewRequest::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $sentCustomer->id,
        'status' => 'sent',
    ]);

    $this->get('/customers?status=reviewed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $reviewedCustomer->id)
        );
});
