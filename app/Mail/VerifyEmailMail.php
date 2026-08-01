<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $verifyUrl,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.verify_email.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verify-email',
            with: [
                'userName' => $this->user->name,
                'verifyUrl' => $this->verifyUrl,
                'code' => $this->code,
            ],
        );
    }
}
