<?php

namespace App\Enums;

enum BillingPaymentEventKind: string
{
    case CheckoutStarted = 'checkout_started';
    case PaymentSucceeded = 'payment_succeeded';
    case PaymentFailed = 'payment_failed';
    case CheckoutAbandoned = 'checkout_abandoned';
    case RefundInitiated = 'refund_initiated';
    case RefundSucceeded = 'refund_succeeded';
}
