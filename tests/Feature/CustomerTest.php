<?php

use App\Models\Business;
use App\Models\Customer;
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
