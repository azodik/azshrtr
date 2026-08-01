<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $consoleUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.welcome.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.welcome',
            with: [
                'userName' => $this->user->name,
                'consoleUrl' => $this->consoleUrl,
            ],
        );
    }
}
