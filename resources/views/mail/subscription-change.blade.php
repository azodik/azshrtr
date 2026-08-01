@php
    $prefix = 'mail.subscription_'.$kind;
@endphp
<x-mail::message>
# {{ __($prefix.'.heading') }}

{{ __($prefix.'.hi', ['name' => $userName]) }}

{{ __($prefix.'.body', [
    'organization' => $organizationName,
    'date' => $effectiveDate ?? '—',
]) }}

@if ($kind === 'downgrade_scheduled' && filled($effectiveDate))
{{ __($prefix.'.effective', ['date' => $effectiveDate]) }}
@endif

<x-mail::button :url="$billingUrl">
{{ __($prefix.'.cta') }}
</x-mail::button>

{{ __($prefix.'.muted') }}

{{ __($prefix.'.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
