<?php

namespace App\Services\JobPilot;

use App\Models\Application;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sends an approved application through Laravel's configured mailer.
 *
 * Callers must have verified the approval + content hash before calling.
 */
class ApplicationDispatcher
{
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

        if (! $draft->recipient_email) {
            throw new RuntimeException('Draft has no recipient email address.');
        }

        $disk = Storage::disk((string) config('jobpilot.cv_disk', 'local'));
        $messageId = (string) Str::uuid();

        Mail::raw((string) $draft->body, function (Message $message) use ($draft, $disk, $messageId): void {
            $message->to((string) $draft->recipient_email)
                ->subject((string) $draft->subject)
                ->replyTo($draft->user->email, $draft->user->name);

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
