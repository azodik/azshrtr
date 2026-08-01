<?php

namespace App\Enums;

enum ApiKeyScope: string
{
    case LinksRead = 'links:read';
    case LinksWrite = 'links:write';
    case QrWrite = 'qr:write';
    case DomainsRead = 'domains:read';
    case AnalyticsRead = 'analytics:read';

    /** @return list<string> */
    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
