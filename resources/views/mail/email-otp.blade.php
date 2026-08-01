<x-mail::message>
# {{ __('mail.email_otp.heading') }}

{{ __('mail.email_otp.hi', ['name' => $userName]) }}

{{ __('mail.email_otp.body') }}

**{{ $code }}**

{{ __('mail.email_otp.muted') }}

{{ __('mail.email_otp.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
