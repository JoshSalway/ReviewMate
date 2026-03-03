<?php

use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access templates page', function () {
    auth()->logout();
    $this->get('/templates')->assertRedirect('/login');
});

test('users can view their templates', function () {
    EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
    ]);

    $this->get('/templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('templates/index'));
});

test('users can update a template', function () {
    $template = EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
    ]);

    $this->put("/templates/{$template->id}", [
        'subject' => 'New subject line',
        'body' => 'New body content here',
    ])->assertRedirect();

    expect($template->fresh()->subject)->toBe('New subject line')
        ->and($template->fresh()->body)->toBe('New body content here');
});

test('users cannot update templates from other businesses', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $template = EmailTemplate::factory()->create(['business_id' => $otherBusiness->id]);

    $this->put("/templates/{$template->id}", [
        'subject' => 'Hacked',
        'body' => 'Hacked body',
    ])->assertForbidden();
});

test('template body is required', function () {
    $template = EmailTemplate::factory()->create(['business_id' => $this->business->id]);

    $this->put("/templates/{$template->id}", ['body' => ''])
        ->assertSessionHasErrors('body');
});
