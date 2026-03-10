<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('users can import customers from csv', function () {
    $this->post('/customers/import', [
        'customers' => [
            ['name' => 'John Smith', 'email' => 'john@example.com', 'phone' => '0400111222'],
            ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '0400333444'],
        ],
    ])->assertRedirect();

    expect($this->business->customers()->count())->toBe(2);
    $this->assertDatabaseHas('customers', [
        'business_id' => $this->business->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);
});

test('csv import skips rows without a name or email', function () {
    $this->post('/customers/import', [
        'customers' => [
            ['name' => '', 'email' => ''],
            ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
        ],
    ])->assertRedirect();

    expect($this->business->customers()->count())->toBe(1);
});

test('csv import does not create duplicates for existing email', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Existing',
        'email' => 'john@example.com',
    ]);

    $this->post('/customers/import', [
        'customers' => [
            ['name' => 'John Smith', 'email' => 'john@example.com'],
        ],
    ])->assertRedirect();

    expect($this->business->customers()->count())->toBe(1);
});

test('csv import requires customers array', function () {
    $this->post('/customers/import', [])
        ->assertSessionHasErrors('customers');
});

test('csv import rejects empty customers array', function () {
    $this->post('/customers/import', ['customers' => []])
        ->assertSessionHasErrors('customers');
});

test('csv import rejects more than 500 customers', function () {
    $customers = array_fill(0, 501, ['name' => 'Test', 'email' => 'test@example.com']);

    $this->post('/customers/import', ['customers' => $customers])
        ->assertSessionHasErrors('customers');
});
