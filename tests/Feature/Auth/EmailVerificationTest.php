<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

test('new users are sent a verification email on registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Notification::assertSentTo(User::where('email', 'test@example.com')->sole(), VerifyEmail::class);
});

test('unverified users can still use the app', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('applications.index'))->assertOk();
});

test('verification notice screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/verify-email'));
});

test('verifying the email leads to the email settings page', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('mail-account.edit', ['verified' => 1]));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});
