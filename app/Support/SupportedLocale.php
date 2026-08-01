<?php

namespace App\Support;

final class SupportedLocale
{
    /** @var list<string> */
    public const ALL = ['en', 'es', 'fr', 'de', 'hi'];

    public const DEFAULT = 'en';

    public static function normalize(?string $locale): string
    {
        if ($locale === null || $locale === '') {
            return self::DEFAULT;
        }

        $base = strtolower(substr(str_replace('_', '-', $locale), 0, 2));

        return in_array($base, self::ALL, true) ? $base : self::DEFAULT;
    }

    public static function fromRequest(?string $explicit = null): string
    {
        if (is_string($explicit) && $explicit !== '') {
            return self::normalize($explicit);
        }

        $header = request()?->header('Accept-Language');
        if (! is_string($header) || $header === '') {
            return self::DEFAULT;
        }

        $primary = trim(explode(',', $header)[0] ?? '');
        $primary = trim(explode(';', $primary)[0] ?? '');

        return self::normalize($primary);
    }
}
