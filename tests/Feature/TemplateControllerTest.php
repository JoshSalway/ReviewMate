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

test('user can view templates page', function () {
    $this->get('/templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('templates/index')
            ->has('templates')
            ->has('business')
        );
});

test('templates page exposes business name and type', function () {
    $this->get('/templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.name', $this->business->name)
            ->where('business.type', $this->business->type)
        );
});

test('user can update an email template', function () {
    $template = EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
        'subject' => 'Old Subject',
        'body' => 'Old body content',
    ]);

    $this->put("/templates/{$template->id}", [
        'subject' => 'New Subject',
        'body' => 'Updated body content for the template.',
    ])->assertRedirect()
      ->assertSessionHas('success');

    expect($template->fresh()->subject)->toBe('New Subject');
    expect($template->fresh()->body)->toBe('Updated body content for the template.');
});

test('template body is required', function () {
    $template = EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
    ]);

    $this->put("/templates/{$template->id}", [
        'subject' => 'Valid Subject',
        'body' => '',
    ])->assertSessionHasErrors('body');
});

test('template body cannot exceed 5000 chars', function () {
    $template = EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
    ]);

    $this->put("/templates/{$template->id}", [
        'body' => str_repeat('x', 5001),
    ])->assertSessionHasErrors('body');
});

test('user cannot update template from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $template = EmailTemplate::factory()->create([
        'business_id' => $otherBusiness->id,
        'type' => 'request',
    ]);

    $this->put("/templates/{$template->id}", [
        'subject' => 'Hacked',
        'body' => 'Hacked content',
    ])->assertForbidden();
});

test('template subject is optional', function () {
    $template = EmailTemplate::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'request',
        'subject' => 'Some subject',
    ]);

    $this->put("/templates/{$template->id}", [
        'body' => 'Valid body content here',
    ])->assertSessionMissing('error');
});

test('EmailTemplate renderBody replaces variable placeholders', function () {
    $template = new EmailTemplate([
        'body' => 'Hello {name}, please leave a review for {business}.',
    ]);

    $result = $template->renderBody([
        'name' => 'Alice',
        'business' => 'Test Cafe',
    ]);

    expect($result)->toBe('Hello Alice, please leave a review for Test Cafe.');
});
