<?php

// ── Public marketing pages smoke tests ──────────────────────────────────────
//
// The landing page (/) is covered by WaitlistTest.php.
// LegalTest.php covers /terms, /privacy, /login.
// This file covers the remaining public marketing routes:
//   /pricing, /features, /changelog

test('GET /pricing returns 200 and renders pricing component', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('pricing'));
});

test('GET /features returns 200 and renders features component', function () {
    $this->get('/features')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('features'));
});

test('GET /changelog returns 200 and renders changelog component', function () {
    $this->get('/changelog')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('changelog'));
});

test('public marketing pages are accessible without authentication', function () {
    auth()->logout();

    $this->get('/pricing')->assertOk();
    $this->get('/features')->assertOk();
    $this->get('/changelog')->assertOk();
});
