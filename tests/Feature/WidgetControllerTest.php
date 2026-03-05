<?php

use App\Models\Business;
use App\Models\Review;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create([
        'user_id' => $this->user->id,
        'name' => 'Daves Plumbing',
        'slug' => 'daves-plumbing',
        'widget_enabled' => true,
        'widget_min_rating' => 4,
        'widget_max_reviews' => 6,
        'widget_theme' => 'light',
        'google_rating' => 4.8,
        'google_review_count' => 42,
    ]);
});

test('widget endpoint returns json for valid slug', function () {
    $this->getJson('/api/widget/daves-plumbing')
        ->assertOk()
        ->assertJsonStructure([
            'business' => ['name', 'rating', 'review_count'],
            'reviews',
            'powered_by_url',
        ])
        ->assertJsonPath('business.name', 'Daves Plumbing')
        ->assertJsonPath('business.rating', 4.8)
        ->assertJsonPath('business.review_count', 42);
});

test('widget endpoint returns reviews with correct structure', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Excellent work!',
        'reviewer_name' => 'John S.',
        'source' => 'google',
        'reviewed_at' => now()->subWeek(),
    ]);

    $response = $this->getJson('/api/widget/daves-plumbing')->assertOk();

    $reviews = $response->json('reviews');
    expect($reviews)->toHaveCount(1);
    expect($reviews[0]['reviewer_name'])->toBe('John S.');
    expect($reviews[0]['rating'])->toBe(5);
    expect($reviews[0]['body'])->toBe('Excellent work!');
    expect($reviews[0]['source'])->toBe('google');
});

test('widget respects min_rating filter', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Great service!',
        'reviewer_name' => 'Alice',
        'reviewed_at' => now()->subDay(),
    ]);

    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 3,
        'body' => 'It was okay.',
        'reviewer_name' => 'Bob',
        'reviewed_at' => now()->subDays(2),
    ]);

    // min_rating is 4 — the 3-star review should be excluded
    $reviews = $this->getJson('/api/widget/daves-plumbing')->json('reviews');

    expect($reviews)->toHaveCount(1);
    expect($reviews[0]['reviewer_name'])->toBe('Alice');
});

test('widget excludes reviews without body', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => null,
        'reviewer_name' => 'Silent Reviewer',
        'reviewed_at' => now()->subDay(),
    ]);

    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Has a comment!',
        'reviewer_name' => 'Chatty Reviewer',
        'reviewed_at' => now()->subDays(2),
    ]);

    $reviews = $this->getJson('/api/widget/daves-plumbing')->json('reviews');

    expect($reviews)->toHaveCount(1);
    expect($reviews[0]['reviewer_name'])->toBe('Chatty Reviewer');
});

test('widget respects max_reviews limit', function () {
    $this->business->update(['widget_max_reviews' => 3]);

    Review::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Great!',
        'reviewed_at' => now()->subDays(rand(1, 10)),
    ]);

    $reviews = $this->getJson('/api/widget/daves-plumbing')->json('reviews');

    expect($reviews)->toHaveCount(3);
});

test('widget returns 404 for unknown slug', function () {
    $this->getJson('/api/widget/unknown-business')->assertNotFound();
});

test('widget returns 404 when widget is disabled', function () {
    $this->business->update(['widget_enabled' => false]);

    $this->getJson('/api/widget/daves-plumbing')->assertNotFound();
});

test('widget includes powered_by_url', function () {
    $poweredByUrl = $this->getJson('/api/widget/daves-plumbing')->json('powered_by_url');

    expect($poweredByUrl)->toContain('reviewmate.app');
    expect($poweredByUrl)->toContain('ref=widget');
});

test('widget settings page is accessible to authenticated users', function () {
    $this->actingAs($this->user)
        ->get('/settings/widget')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/widget'));
});

test('widget settings can be updated', function () {
    $this->actingAs($this->user)
        ->put('/settings/widget', [
            'widget_enabled' => false,
            'widget_min_rating' => 5,
            'widget_max_reviews' => 3,
            'widget_theme' => 'dark',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'id' => $this->business->id,
        'widget_enabled' => false,
        'widget_min_rating' => 5,
        'widget_max_reviews' => 3,
        'widget_theme' => 'dark',
    ]);
});

test('widget settings exposes embed code to frontend', function () {
    $this->actingAs($this->user)
        ->get('/settings/widget')
        ->assertInertia(fn ($page) => $page
            ->has('embedCode')
            ->where('embedCode', fn ($code) => str_contains((string) $code, 'daves-plumbing'))
        );
});

test('widget returns newest reviews first', function () {
    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'Old review',
        'reviewer_name' => 'Old',
        'reviewed_at' => now()->subDays(10),
    ]);

    Review::factory()->create([
        'business_id' => $this->business->id,
        'rating' => 5,
        'body' => 'New review',
        'reviewer_name' => 'New',
        'reviewed_at' => now()->subDay(),
    ]);

    $reviews = $this->getJson('/api/widget/daves-plumbing')->json('reviews');

    expect($reviews[0]['reviewer_name'])->toBe('New');
    expect($reviews[1]['reviewer_name'])->toBe('Old');
});
