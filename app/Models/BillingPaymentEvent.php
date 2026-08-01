<?php

namespace App\Models;

use App\Enums\BillingPaymentEventKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPaymentEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'kind',
        'provider',
        'provider_event_id',
        'provider_payment_id',
        'provider_checkout_session_id',
        'provider_subscription_id',
        'provider_customer_id',
        'status',
        'currency',
        'amount_cents',
        'error_code',
        'error_message',
        'email_sent',
        'emailed_at',
        'payload',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'kind' => BillingPaymentEventKind::class,
            'amount_cents' => 'integer',
            'email_sent' => 'boolean',
            'emailed_at' => 'datetime',
            'payload' => 'array',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
