<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\MailAccountUpdateRequest;
use App\Services\JobPilot\UserMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailAccountController extends Controller
{
    /**
     * Show the email settings page: login email verification and the connected mailbox.
     */
    public function edit(Request $request, UserMailer $mailer): Response
    {
        $user = $request->user();
        $account = $user->mailAccount;

        return Inertia::render('settings/email', [
            'emailVerified' => $user->hasVerifiedEmail(),
            'justVerified' => $request->boolean('verified'),
            'status' => $request->session()->get('status'),
            'providers' => collect($mailer->providers())
                ->map(fn (array $preset, string $key): array => [
                    'value' => $key,
                    'label' => $preset['label'],
                    'appPasswordUrl' => $preset['app_password_url'],
                ])
                ->values(),
            'suggestedProvider' => $mailer->suggestedProvider($user->email),
            'mailAccount' => $account ? [
                'provider' => $account->provider,
                'connectedAt' => $account->updated_at,
            ] : null,
        ]);
    }

    /**
     * Connect (or reconnect) the user's mailbox after confirming the app password works.
     */
    public function update(MailAccountUpdateRequest $request, UserMailer $mailer): RedirectResponse
    {
        $user = $request->user();
        $provider = $request->validated('provider');
        $password = $request->validated('password');

        try {
            $mailer->testConnection($user, $provider, $password);
        } catch (TransportExceptionInterface $e) {
            if (str_contains($e->getMessage(), 'authenticate')) {
                throw ValidationException::withMessages([
                    'password' => __('Your provider rejected this app password for :email. Make sure you pasted an app password (not your normal password) for this account.', ['email' => $user->email]),
                ]);
            }

            report($e);

            throw ValidationException::withMessages([
                'password' => __('Could not reach your provider’s mail server. Try again in a moment.'),
            ]);
        }

        $user->mailAccount()->updateOrCreate([], [
            'provider' => $provider,
            'password' => $password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mailbox connected.')]);

        return to_route('mail-account.edit');
    }

    /**
     * Disconnect the mailbox and forget its app password.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->mailAccount()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mailbox disconnected.')]);

        return to_route('mail-account.edit');
    }
}
