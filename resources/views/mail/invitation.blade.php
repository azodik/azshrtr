@php
    $prefix = 'mail.invitation_'.$kind;
@endphp
<x-mail::message>
# {{ __($prefix.'.heading') }}

{{ __($prefix.'.hi', ['name' => $userName]) }}

{{ __($prefix.'.body', [
    'organization' => $organizationName,
    'inviter' => $inviterName,
    'role' => $roleLabel,
    'member' => $memberName ?? $userName,
]) }}

@if (filled($expiresLabel) && in_array($kind, ['invited', 'resent'], true))
{{ __($prefix.'.expires', ['date' => $expiresLabel]) }}
@endif

<x-mail::button :url="$actionUrl">
{{ __($prefix.'.cta') }}
</x-mail::button>

{{ __($prefix.'.muted') }}

{{ __($prefix.'.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
