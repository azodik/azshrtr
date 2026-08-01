<x-mail::message>
# {{ __('mail.welcome.heading') }}

{{ __('mail.welcome.hi', ['name' => $userName]) }}

{{ __('mail.welcome.body') }}

<x-mail::button :url="$consoleUrl">
{{ __('mail.welcome.cta') }}
</x-mail::button>

{{ __('mail.welcome.muted') }}

{{ __('mail.welcome.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
