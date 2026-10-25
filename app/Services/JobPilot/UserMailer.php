<?php

namespace App\Services\JobPilot;

use App\Models\MailAccount;
use App\Models\User;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Sends mail through a user's own mailbox over SMTP.
 *
 * Hosts are limited to the provider presets in config('jobpilot.mail_providers')
 * so users can never point the server at an arbitrary host.
 */
class UserMailer
{
    /**
     * @return array<string, array{label: string, host: string, port: int, app_password_url: string}>
     */
    public function providers(): array
    {
        return config('jobpilot.mail_providers');
    }

    /**
     * Guess the provider preset from the domain of an email address.
     */
    public function suggestedProvider(string $email): string
    {
        $domain = strtolower((string) str($email)->after('@'));

        return match (true) {
            str_starts_with($domain, 'yahoo.') || $domain === 'ymail.com' => 'yahoo',
            in_array($domain, ['icloud.com', 'me.com', 'mac.com'], true) => 'icloud',
            default => 'gmail',
        };
    }

    /**
     * Sign in to the provider's SMTP server and sign out again.
     *
     * @throws TransportExceptionInterface when the credentials are rejected or the server is unreachable
     */
    public function testConnection(User $user, string $provider, string $password): void
    {
        $transport = $this->build($user, $provider, $password)->getSymfonyTransport();

        if ($transport instanceof EsmtpTransport) {
            $transport->start();
            $transport->stop();
        }
    }

    /**
     * A mailer that sends through the given mailbox.
     */
    public function mailerFor(MailAccount $account): Mailer
    {
        return $this->build($account->user, $account->provider, $account->password);
    }

    private function build(User $user, string $provider, string $password): Mailer
    {
        $preset = $this->providers()[$provider];

        return Mail::build([
            'transport' => 'smtp',
            'host' => $preset['host'],
            'port' => $preset['port'],
            'username' => $user->email,
            'password' => $password,
            'timeout' => 15,
        ]);
    }
}
