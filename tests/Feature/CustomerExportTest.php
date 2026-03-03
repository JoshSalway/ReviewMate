<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('user can export customers as csv', function () {
    Customer::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->get('/customers/export');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('.csv');
});

test('csv export contains header row', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Alice Brown',
        'email' => 'alice@example.com',
    ]);

    $content = $this->get('/customers/export')->streamedContent();

    expect($content)->toContain('Name');
    expect($content)->toContain('Email');
    expect($content)->toContain('Alice Brown');
    expect($content)->toContain('alice@example.com');
});

test('csv export is scoped to current business', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'My Customer']);

    $otherBusiness = Business::factory()->onboarded()->create();
    Customer::factory()->create(['business_id' => $otherBusiness->id, 'name' => 'Other Customer']);

    $content = $this->get('/customers/export')->streamedContent();

    expect($content)->toContain('My Customer');
    expect($content)->not->toContain('Other Customer');
});

test('guests cannot export customers', function () {
    auth()->logout();
    $this->get('/customers/export')->assertRedirect('/login');
});
