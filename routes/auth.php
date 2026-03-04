<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\WorkOS\Http\Requests\AuthKitAuthenticationRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLoginRequest;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;

Route::middleware(['guest'])->group(function () {
    Route::get('login', fn () => Inertia::render('auth/login'))->name('login');

    Route::get('auth/redirect', fn (AuthKitLoginRequest $request) => $request->redirect())->name('auth.redirect');

    Route::get('authenticate', fn (AuthKitAuthenticationRequest $request) => tap(
        redirect()->intended(route('dashboard')),
        fn () => $request->authenticate(),
    ));
});

Route::post('logout', fn (AuthKitLogoutRequest $request) => $request->logout())
    ->middleware(['auth'])->name('logout');
