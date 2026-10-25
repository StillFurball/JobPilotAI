<?php

use App\Models\MailAccount;
use App\Models\User;
use App\Services\JobPilot\UserMailer;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;

test('email settings page suggests a provider from the login email', function () {
    $user = User::factory()->create(['email' => 'budi@yahoo.co.id']);

    $this->actingAs($user)
        ->get(route('mail-account.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/email')
            ->where('emailVerified', true)
            ->where('suggestedProvider', 'yahoo')
            ->where('mailAccount', null));
});

test('a connected mailbox never exposes its app password', function () {
    $user = User::factory()->create();
    MailAccount::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('mail-account.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('mailAccount.provider', 'gmail')
            ->missing('mailAccount.password'))
        ->assertDontSee('abcdefghijklmnop');
});

test('users can connect their mailbox once the app password signs in', function () {
    $user = User::factory()->create();

    $this->partialMock(UserMailer::class)
        ->shouldReceive('testConnection')
        ->once()
        ->withArgs(fn (User $u, string $provider, string $password) => $u->is($user)
            && $provider === 'gmail'
            && $password === 'abcdefghijklmnop');

    $this->actingAs($user)
        ->put(route('mail-account.update'), ['provider' => 'gmail', 'password' => 'abcd efgh ijkl mnop'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('mail-account.edit'));

    expect($user->mailAccount->password)->toBe('abcdefghijklmnop')
        ->and(DB::table('mail_accounts')->value('password'))->not->toContain('abcdefghijklmnop');
});

test('a rejected app password is not saved', function () {
    $user = User::factory()->create();

    $this->partialMock(UserMailer::class)
        ->shouldReceive('testConnection')
        ->andThrow(new TransportException('Failed to authenticate on SMTP server with username "x"'));

    $this->actingAs($user)
        ->put(route('mail-account.update'), ['provider' => 'gmail', 'password' => 'wrong'])
        ->assertSessionHasErrors(['password' => __('Your provider rejected this app password for :email. Make sure you pasted an app password (not your normal password) for this account.', ['email' => $user->email])]);

    expect($user->mailAccount()->exists())->toBeFalse();
});

test('only the preset providers can be used', function () {
    $user = User::factory()->create();

    $this->partialMock(UserMailer::class)->shouldNotReceive('testConnection');

    $this->actingAs($user)
        ->put(route('mail-account.update'), ['provider' => 'internal.example:6379', 'password' => 'secret'])
        ->assertSessionHasErrors('provider');
});

test('users can disconnect their mailbox', function () {
    $user = User::factory()->create();
    MailAccount::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('mail-account.destroy'))
        ->assertRedirect(route('mail-account.edit'));

    expect($user->mailAccount()->exists())->toBeFalse();
});
