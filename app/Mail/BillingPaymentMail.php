<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillingPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'payment_succeeded'|'payment_failed'|'checkout_abandoned'|'refund_initiated'|'refund_succeeded'  $kind
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $userName,
        public readonly string $organizationName,
        public readonly string $billingUrl,
        public readonly ?string $amountLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.billing_'.$this->kind.'.subject', [
                'organization' => $this->organizationName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.billing-payment',
            with: [
                'kind' => $this->kind,
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'billingUrl' => $this->billingUrl,
                'amountLabel' => $this->amountLabel,
            ],
        );
    }
}
