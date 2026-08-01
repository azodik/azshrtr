<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'invited'|'resent'|'accepted'|'accepted_admin'|'revoked'  $kind
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $userName,
        public readonly string $organizationName,
        public readonly string $inviterName,
        public readonly string $roleLabel,
        public readonly string $actionUrl,
        public readonly ?string $expiresLabel = null,
        public readonly ?string $memberName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.invitation_'.$this->kind.'.subject', [
                'organization' => $this->organizationName,
                'member' => $this->memberName ?? $this->userName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'kind' => $this->kind,
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'inviterName' => $this->inviterName,
                'roleLabel' => $this->roleLabel,
                'actionUrl' => $this->actionUrl,
                'expiresLabel' => $this->expiresLabel,
                'memberName' => $this->memberName,
            ],
        );
    }
}
