<x-mail::message>
# {{ __('mail.password_reset.heading') }}

{{ __('mail.password_reset.hi', ['name' => $userName]) }}

{{ __('mail.password_reset.body') }}

<x-mail::button :url="$resetUrl">
{{ __('mail.password_reset.cta') }}
</x-mail::button>

{{ __('mail.password_reset.muted', ['minutes' => $expiresMinutes]) }}

{{ __('mail.password_reset.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
