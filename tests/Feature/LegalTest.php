<?php

test('GET /terms returns 200', function () {
    $this->get('/terms')->assertOk()->assertInertia(
        fn ($page) => $page->component('legal/terms')
    );
});

test('GET /privacy returns 200', function () {
    $this->get('/privacy')->assertOk()->assertInertia(
        fn ($page) => $page->component('legal/privacy')
    );
});

test('GET /login renders login page', function () {
    $this->get('/login')->assertOk()->assertInertia(
        fn ($page) => $page->component('auth/login')
    );
});
