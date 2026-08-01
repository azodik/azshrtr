@php
    $metric = __('mail.usage_limit.metric_'.$metricKey);
@endphp
<x-mail::message>
# {{ __('mail.usage_limit.heading') }}

{{ __('mail.usage_limit.hi', ['name' => $userName]) }}

{!! __('mail.usage_limit.body', [
    'organization' => e($organizationName),
    'metric' => e($metric),
    'plan' => e($planName),
    'used' => e((string) $used),
    'limit' => e((string) $limit),
    'percent' => e($percent),
]) !!}

<x-mail::button :url="$billingUrl">
{{ __('mail.usage_limit.cta') }}
</x-mail::button>

{{ __('mail.usage_limit.muted', ['metric' => $metric]) }}

{{ __('mail.usage_limit.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
