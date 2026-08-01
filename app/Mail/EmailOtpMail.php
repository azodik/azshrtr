<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $userName = 'there',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.email_otp.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.email-otp',
            with: [
                'code' => $this->code,
                'userName' => $this->userName,
            ],
        );
    }
}
