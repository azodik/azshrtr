<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Release identity (SemVer + build number)
    |--------------------------------------------------------------------------
    |
    | VERSION file is the SemVer source of truth. CI stamps build / commit into
    | the Docker image (build-info.json + AZSHRTR_* env). package.json may mirror
    | SemVer for tooling but is not authoritative.
    |
    */

    'release' => [
        'version' => env('AZSHRTR_VERSION'),
        'build' => env('AZSHRTR_BUILD'),
        'commit' => env('AZSHRTR_COMMIT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guest anonymous links
    |--------------------------------------------------------------------------
    */

    'guest_link_ttl_minutes' => (int) env('AZSHRTR_GUEST_LINK_TTL_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Usage metering
    |--------------------------------------------------------------------------
    */

    'usage_timezone' => env('AZSHRTR_USAGE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Usage alert emails (percent of Free / capped Pro metrics)
    |--------------------------------------------------------------------------
    */

    'usage_alerts' => [
        'thresholds' => array_values(array_filter(array_map(
            static fn (string $value): float => (float) trim($value),
            explode(',', (string) env('AZSHRTR_USAGE_ALERT_THRESHOLDS', '89,90,100')),
        ), static fn (float $value): bool => $value > 0)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared hosting — drain database queue from 1-minute cron
    |--------------------------------------------------------------------------
    */

    'cron_queue' => filter_var(env('AZSHRTR_CRON_QUEUE', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Free plan monthly caps (calendar month in usage_timezone)
    |--------------------------------------------------------------------------
    */

    'plans' => [
        'free' => [
            'links_per_month' => 3000,
            'qr_per_month' => 300,
            'api_keys' => 2,
            'audit_retention_days' => 7,
            'click_retention_days' => 30,
            'api_log_retention_days' => 7,
        ],
        'pro' => [
            'links_per_month' => null,
            'qr_per_month' => null,
            'api_keys' => 20,
            'audit_retention_days' => 90,
            'click_retention_days' => 365,
            'api_log_retention_days' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    */

    'domains' => [
        'root' => env('AZSHRTR_DOMAIN_ROOT', 'azshrtr.com'),
        'dns_verify' => filter_var(env('AZSHRTR_DOMAIN_DNS_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
        'cname_target' => env('AZSHRTR_CUSTOM_DOMAIN_CNAME_TARGET', 'customers.azshrtr.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Link import / export
    |--------------------------------------------------------------------------
    |
    | Keep max_payload_bytes under PHP post_max_size / nginx client_max_body_size.
    | Docker ships post_max_size=12M; local Herd defaults are often 2M — raise
    | Herd's php.ini or rely on public/.user.ini where the SAPI honors it.
    |
    */

    'import_export' => [
        'max_payload_bytes' => (int) env('AZSHRTR_IMPORT_MAX_PAYLOAD_BYTES', 5_242_880),
        'max_rows' => (int) env('AZSHRTR_IMPORT_MAX_ROWS', 10_000),
    ],

    'cloudflare' => [
        'enabled' => filter_var(env('CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'ssl_method' => env('CLOUDFLARE_CUSTOM_HOSTNAME_SSL_METHOD', 'txt'),
    ],

];
