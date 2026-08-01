<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsageAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'warning'|'limit'  $kind
     */
    public function __construct(
        public readonly string $kind,
        public readonly string $userName,
        public readonly string $organizationName,
        public readonly string $planName,
        public readonly string $metricKey,
        public readonly int $used,
        public readonly int $limit,
        public readonly float $percent,
        public readonly int $threshold,
        public readonly string $billingUrl,
    ) {}

    public function envelope(): Envelope
    {
        $metric = __('mail.usage_warning.metric_'.$this->metricKey);

        if ($this->kind === 'limit') {
            return new Envelope(
                subject: __('mail.usage_limit.subject', ['metric' => $metric]),
            );
        }

        return new Envelope(
            subject: __('mail.usage_warning.subject', [
                'threshold' => $this->threshold,
                'metric' => $metric,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->kind === 'limit' ? 'mail.usage-limit' : 'mail.usage-warning',
            with: [
                'userName' => $this->userName,
                'organizationName' => $this->organizationName,
                'planName' => $this->planName,
                'metricKey' => $this->metricKey,
                'used' => $this->used,
                'limit' => $this->limit,
                'percent' => number_format($this->percent, 1),
                'threshold' => $this->threshold,
                'billingUrl' => $this->billingUrl,
            ],
        );
    }
}
