<?php

use App\Models\WaitlistEntry;

test('waitlist landing page renders', function () {
    $this->get('/')->assertOk()->assertInertia(
        fn ($page) => $page->component('welcome')->has('count')
    );
});

test('waitlist count reflects database', function () {
    WaitlistEntry::factory()->count(5)->create();

    $this->get('/')->assertInertia(
        fn ($page) => $page->where('count', 5)
    );
});

test('user can join waitlist', function () {
    $this->post('/waitlist', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'business_type' => 'cafe',
    ])->assertRedirect();

    $this->assertDatabaseHas('waitlist_entries', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'business_type' => 'cafe',
    ]);
});

test('waitlist rejects duplicate email', function () {
    WaitlistEntry::factory()->create(['email' => 'jane@example.com']);

    $this->post('/waitlist', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ])->assertSessionHasErrors('email');
});

test('waitlist requires name and email', function () {
    $this->post('/waitlist', [])->assertSessionHasErrors(['name', 'email']);
});

test('waitlist accepts entry without business type', function () {
    $this->post('/waitlist', [
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('waitlist_entries', [
        'email' => 'bob@example.com',
        'business_type' => null,
    ]);
});
