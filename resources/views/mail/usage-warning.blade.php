@php
    $metric = __('mail.usage_warning.metric_'.$metricKey);
@endphp
<x-mail::message>
# {{ __('mail.usage_warning.heading') }}

{{ __('mail.usage_warning.hi', ['name' => $userName]) }}

{!! __('mail.usage_warning.body', [
    'organization' => e($organizationName),
    'used' => e((string) $used),
    'limit' => e((string) $limit),
    'metric' => e($metric),
    'percent' => e($percent),
    'plan' => e($planName),
]) !!}

{!! __('mail.usage_warning.threshold', ['threshold' => e((string) $threshold)]) !!}

<x-mail::button :url="$billingUrl">
{{ __('mail.usage_warning.cta') }}
</x-mail::button>

{{ __('mail.usage_warning.muted') }}

{{ __('mail.usage_warning.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
