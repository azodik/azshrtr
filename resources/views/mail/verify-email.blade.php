<x-mail::message>
# {{ __('mail.verify_email.heading') }}

{{ __('mail.verify_email.hi', ['name' => $userName]) }}

{{ __('mail.verify_email.body') }}

**{{ $code }}**

<x-mail::button :url="$verifyUrl">
{{ __('mail.verify_email.cta') }}
</x-mail::button>

{{ __('mail.verify_email.muted') }}

{{ __('mail.verify_email.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
