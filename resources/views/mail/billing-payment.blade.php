@php
    $prefix = 'mail.billing_'.$kind;
@endphp
<x-mail::message>
# {{ __($prefix.'.heading') }}

{{ __($prefix.'.hi', ['name' => $userName]) }}

{{ __($prefix.'.body', [
    'organization' => $organizationName,
    'amount' => $amountLabel ?? '—',
]) }}

@if (filled($amountLabel) && in_array($kind, ['payment_succeeded', 'payment_failed', 'refund_initiated', 'refund_succeeded'], true))
{{ __($prefix.'.amount', ['amount' => $amountLabel]) }}
@endif

<x-mail::button :url="$billingUrl">
{{ __($prefix.'.cta') }}
</x-mail::button>

{{ __($prefix.'.muted') }}

{{ __($prefix.'.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
