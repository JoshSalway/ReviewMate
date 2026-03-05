<?php

use App\Models\User;

test('user has default role of user', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe('user');
    expect($user->isSuperAdmin())->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
});

test('user can be set to superadmin role', function () {
    $user = User::factory()->create(['role' => 'superadmin', 'is_admin' => true]);

    expect($user->isSuperAdmin())->toBeTrue();
    expect($user->isAdmin())->toBeTrue();
    expect($user->hasRole('superadmin'))->toBeTrue();
});

test('superadmin can access admin route', function () {
    $user = User::factory()->create(['role' => 'superadmin', 'is_admin' => true]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(200);
});

test('regular user cannot access admin route', function () {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});
