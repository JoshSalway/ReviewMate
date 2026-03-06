<?php

use App\Mail\WaitlistApprovedMail;
use App\Mail\WaitlistConfirmationMail;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Mail;

test('waitlist landing page renders', function () {
    $this->get('/')->assertOk()->assertInertia(
        fn ($page) => $page->component('welcome')->has('count')
    );
});

test('waitlist page renders', function () {
    $this->get('/waitlist')->assertOk()->assertInertia(
        fn ($page) => $page->component('waitlist')->has('count')
    );
});

test('waitlist count reflects database', function () {
    WaitlistEntry::factory()->count(5)->create();

    $this->get('/')->assertInertia(
        fn ($page) => $page->where('count', 5)
    );
});

test('guest can submit waitlist form', function () {
    Mail::fake();

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

test('confirmation email is queued after signup', function () {
    Mail::fake();

    $this->post('/waitlist', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    Mail::assertQueued(WaitlistConfirmationMail::class, function ($mail) {
        return $mail->hasTo('jane@example.com');
    });
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
    Mail::fake();

    $this->post('/waitlist', [
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('waitlist_entries', [
        'email' => 'bob@example.com',
        'business_type' => null,
    ]);
});

// -- RequireBetaAccess middleware tests --
// We hit /analytics (a simple auth-only route that returns 200 once logged in
// and has no onboarding redirect) to test whether the beta middleware allows
// or blocks access.

test('beta mode off: all authenticated users pass through', function () {
    config(['app.beta_mode' => false]);

    $user = User::factory()->create(['role' => 'user']);

    // Beta off — middleware is a no-op; the request proceeds past it.
    // The middleware itself should not redirect, so we get past the beta gate.
    $response = $this->actingAs($user)->get('/analytics');
    $response->assertStatus(200)->assertOk();
})->skip('ValidateSessionWithWorkOS blocks actingAs in test env — middleware unit tested below');

test('beta mode on: superadmin passes through', function () {
    config(['app.beta_mode' => true]);

    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->get('/analytics');
    $response->assertOk();
})->skip('ValidateSessionWithWorkOS blocks actingAs in test env — middleware unit tested below');

test('beta mode on: unapproved authenticated user is redirected to /waitlist', function () {
    config(['app.beta_mode' => true]);

    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get('/analytics')->assertRedirect('/waitlist');
});

test('beta mode on: approved waitlist user passes through', function () {
    config(['app.beta_mode' => true]);

    $user = User::factory()->create(['role' => 'user']);
    WaitlistEntry::factory()->create([
        'email' => $user->email,
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/analytics');
    $response->assertOk();
})->skip('ValidateSessionWithWorkOS blocks actingAs in test env — middleware unit tested below');

test('beta mode on: pending waitlist user (not approved) is redirected to /waitlist', function () {
    config(['app.beta_mode' => true]);

    $user = User::factory()->create(['role' => 'user']);
    WaitlistEntry::factory()->create([
        'email' => $user->email,
        'approved_at' => null,
    ]);

    $this->actingAs($user)->get('/analytics')->assertRedirect('/waitlist');
});

// Direct unit tests for RequireBetaAccess middleware logic (no HTTP stack needed)

test('RequireBetaAccess: beta off allows all users through', function () {
    config(['app.beta_mode' => false]);

    $user = User::factory()->create(['role' => 'user']);
    $middleware = new \App\Http\Middleware\RequireBetaAccess;

    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $called = false;
    $response = $middleware->handle($request, function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('RequireBetaAccess: beta on blocks unapproved user', function () {
    config(['app.beta_mode' => true]);

    $user = User::factory()->create(['role' => 'user']);
    $middleware = new \App\Http\Middleware\RequireBetaAccess;

    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $called = false;
    $response = $middleware->handle($request, function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeFalse();
    expect($response->getStatusCode())->toBe(302);
});

test('RequireBetaAccess: beta on allows superadmin', function () {
    config(['app.beta_mode' => true]);

    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $middleware = new \App\Http\Middleware\RequireBetaAccess;

    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $superadmin);

    $called = false;
    $middleware->handle($request, function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

test('RequireBetaAccess: beta on allows approved waitlist user', function () {
    config(['app.beta_mode' => true]);

    $user = User::factory()->create(['role' => 'user']);
    WaitlistEntry::factory()->create([
        'email' => $user->email,
        'approved_at' => now(),
    ]);

    $middleware = new \App\Http\Middleware\RequireBetaAccess;
    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);

    $called = false;
    $middleware->handle($request, function () use (&$called) {
        $called = true;

        return response('ok');
    });

    expect($called)->toBeTrue();
});

// -- Admin approve tests --

test('superadmin can approve a waitlist entry', function () {
    Mail::fake();

    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $entry = WaitlistEntry::factory()->create(['approved_at' => null]);

    $this->actingAs($superadmin)
        ->post("/admin/waitlist/{$entry->id}/approve")
        ->assertRedirect();

    $entry->refresh();
    expect($entry->approved_at)->not->toBeNull();
});

test('approved mail is queued when entry is approved', function () {
    Mail::fake();

    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $entry = WaitlistEntry::factory()->create(['approved_at' => null]);

    $this->actingAs($superadmin)
        ->post("/admin/waitlist/{$entry->id}/approve");

    Mail::assertQueued(WaitlistApprovedMail::class, function ($mail) use ($entry) {
        return $mail->hasTo($entry->email);
    });
});

test('non-superadmin cannot approve waitlist entries', function () {
    $user = User::factory()->create(['role' => 'user']);
    $entry = WaitlistEntry::factory()->create(['approved_at' => null]);

    $this->actingAs($user)
        ->post("/admin/waitlist/{$entry->id}/approve")
        ->assertStatus(403);
});

// -- WaitlistEntry model scopes --

test('pending scope returns only unapproved entries', function () {
    WaitlistEntry::factory()->create(['approved_at' => null]);
    WaitlistEntry::factory()->create(['approved_at' => now()]);

    expect(WaitlistEntry::pending()->count())->toBe(1);
});

test('approved scope returns only approved entries', function () {
    WaitlistEntry::factory()->create(['approved_at' => null]);
    WaitlistEntry::factory()->create(['approved_at' => now()]);
    WaitlistEntry::factory()->create(['approved_at' => now()]);

    expect(WaitlistEntry::approved()->count())->toBe(2);
});
