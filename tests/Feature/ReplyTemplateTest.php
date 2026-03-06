<?php

use App\Models\Business;
use App\Models\ReplyTemplate;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

test('guests cannot access reply templates page', function () {
    auth()->logout();
    $this->get('/settings/reply-templates')->assertRedirect('/login');
});

test('user can view reply templates page', function () {
    $this->get('/settings/reply-templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/reply-templates'));
});

test('user without completed onboarding is redirected from reply templates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/settings/reply-templates')
        ->assertRedirect(route('onboarding.business-type'));
});

test('user can create a reply template', function () {
    $this->post('/settings/reply-templates', [
        'name' => 'Thank You Template',
        'body' => 'Thank you so much for your kind words!',
    ])->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('reply_templates', [
        'business_id' => $this->business->id,
        'name' => 'Thank You Template',
        'body' => 'Thank you so much for your kind words!',
    ]);
});

test('reply template name and body are required', function () {
    $this->post('/settings/reply-templates', [])
        ->assertSessionHasErrors(['name', 'body']);
});

test('reply template name is limited to 100 chars', function () {
    $this->post('/settings/reply-templates', [
        'name' => str_repeat('x', 101),
        'body' => 'Some body text',
    ])->assertSessionHasErrors('name');
});

test('reply template body is limited to 4096 chars', function () {
    $this->post('/settings/reply-templates', [
        'name' => 'Valid Name',
        'body' => str_repeat('x', 4097),
    ])->assertSessionHasErrors('body');
});

test('user can update their reply template', function () {
    $template = ReplyTemplate::create([
        'business_id' => $this->business->id,
        'name' => 'Old Name',
        'body' => 'Old body',
    ]);

    $this->put("/settings/reply-templates/{$template->id}", [
        'name' => 'New Name',
        'body' => 'New body text',
    ])->assertRedirect()
        ->assertSessionHas('success');

    expect($template->fresh()->name)->toBe('New Name');
    expect($template->fresh()->body)->toBe('New body text');
});

test('user cannot update reply template from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $template = ReplyTemplate::create([
        'business_id' => $otherBusiness->id,
        'name' => 'Other Template',
        'body' => 'Body',
    ]);

    $this->put("/settings/reply-templates/{$template->id}", [
        'name' => 'Hacked',
        'body' => 'Hacked body',
    ])->assertForbidden();
});

test('user can delete their reply template', function () {
    $template = ReplyTemplate::create([
        'business_id' => $this->business->id,
        'name' => 'To Delete',
        'body' => 'Deletable',
    ]);

    $this->delete("/settings/reply-templates/{$template->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertModelMissing($template);
});

test('user cannot delete reply template from another business', function () {
    $otherBusiness = Business::factory()->onboarded()->create();
    $template = ReplyTemplate::create([
        'business_id' => $otherBusiness->id,
        'name' => 'Other',
        'body' => 'Other body',
    ]);

    $this->delete("/settings/reply-templates/{$template->id}")
        ->assertForbidden();

    $this->assertModelExists($template);
});

test('reply templates are scoped to current business', function () {
    ReplyTemplate::create(['business_id' => $this->business->id, 'name' => 'Mine', 'body' => 'My body']);

    $otherBusiness = Business::factory()->onboarded()->create();
    ReplyTemplate::create(['business_id' => $otherBusiness->id, 'name' => 'Not Mine', 'body' => 'Other body']);

    $this->get('/settings/reply-templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('templates', 1));
});
