<?php

use App\Models\Business;
use App\Models\User;

test('unauthenticated request to api returns 401', function () {
    $this->getJson('/api/v1/businesses')
        ->assertStatus(401);
});

test('authenticated user can list businesses via api token', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/businesses')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'uuid', 'name', 'type']]]);
});

test('authenticated user can get a single business via api token', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $business->id);
});

test('user cannot access another user business via api', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $otherUser->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}")
        ->assertStatus(403);
});

test('authenticated user can get review stats via api', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/businesses/{$business->id}/stats")
        ->assertOk()
        ->assertJsonStructure(['total_reviews', 'average_rating', 'conversion_rate', 'total_requests', 'pending_replies']);
});

test('api me endpoint returns authenticated user', function () {
    $user = User::factory()->create(['role' => 'user']);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('role', 'user')
        ->assertJsonPath('plan', 'free');
});

test('api token endpoint issues a token via session auth', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/token', ['token_name' => 'My Token'])
        ->assertStatus(201)
        ->assertJsonStructure(['token', 'token_type']);
});
