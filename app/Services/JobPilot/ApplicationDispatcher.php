<?php

namespace App\Services\JobPilot;

use App\Models\Application;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sends an approved application from the user's login email through their own mailbox.
 *
 * Callers must have verified the approval + content hash, the user's email
 * verification and the mailbox connection before calling.
 */
class ApplicationDispatcher
{
    public function __construct(private UserMailer $mailer) {}

    public function enabled(): bool
    {
        return (bool) config('jobpilot.send_emails', false);
    }

    /**
     * Send and return a provider message id.
     */
    public function send(Application $application): string
    {
        $draft = $application->draft;
        $user = $draft->user;

        if (! $draft->recipient_email) {
            throw new RuntimeException('Draft has no recipient email address.');
        }

        if (! $user->mailAccount) {
            throw new RuntimeException('No mailbox is connected to send from.');
        }

        $disk = Storage::disk((string) config('jobpilot.cv_disk', 'local'));
        $messageId = (string) Str::uuid();

        $this->mailer->mailerFor($user->mailAccount)->raw((string) $draft->body, function (Message $message) use ($draft, $user, $disk, $messageId): void {
            $message->from($user->email, $user->name)
                ->to((string) $draft->recipient_email)
                ->subject((string) $draft->subject);

            $message->getHeaders()->addTextHeader('X-JobPilot-Message-Id', $messageId);

            if ($draft->cv_file_path && $disk->exists($draft->cv_file_path)) {
                $extension = pathinfo($draft->cv_file_path, PATHINFO_EXTENSION);
                $message->attachData(
                    (string) $disk->get($draft->cv_file_path),
                    'CV - '.Str::slug($draft->user->name).'.'.$extension,
                    ['mime' => $extension === 'pdf' ? 'application/pdf' : 'text/html'],
                );
            }
        });

        return $messageId;
    }
}
