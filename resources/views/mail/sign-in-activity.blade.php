<x-mail::message>
# {{ __('mail.sign_in_activity.heading') }}

{{ __('mail.sign_in_activity.hi', ['name' => $userName]) }}

{{ __('mail.sign_in_activity.body') }}

- {{ __('mail.sign_in_activity.time', ['time' => $signedInAt]) }}
- {{ __('mail.sign_in_activity.ip', ['ip' => $ipAddress]) }}
- {{ __('mail.sign_in_activity.device', ['device' => $device]) }}

{{ __('mail.sign_in_activity.muted') }}

<x-mail::button :url="$secureUrl">
{{ __('mail.sign_in_activity.cta') }}
</x-mail::button>

{{ __('mail.sign_in_activity.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
