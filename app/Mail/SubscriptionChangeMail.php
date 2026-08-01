<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'upgraded'|'downgrade_scheduled'|'downgraded'  $kind
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $userName,
        public readonly string $organizationName,
        public readonly string $billingUrl,
        public readonly ?string $effectiveDate = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.subscription_'.$this->kind.'.subject', [
                'organization' => $this->organizationName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.subscription-change',
            with: [
                'kind' => $this->kind,
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'billingUrl' => $this->billingUrl,
                'effectiveDate' => $this->effectiveDate,
            ],
        );
    }
}
