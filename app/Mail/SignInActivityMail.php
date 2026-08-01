<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignInActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $signedInAt,
        public readonly string $ipAddress,
        public readonly string $device,
        public readonly string $secureUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.sign_in_activity.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sign-in-activity',
            with: [
                'userName' => $this->user->name,
                'signedInAt' => $this->signedInAt,
                'ipAddress' => $this->ipAddress,
                'device' => $this->device,
                'secureUrl' => $this->secureUrl,
            ],
        );
    }
}
