<?php

namespace App\Support;

final class BuildInfo
{
    /**
     * @return array{version: string, build: string|null, commit: string|null}
     */
    public static function toArray(): array
    {
        $fromFile = self::fromBuildInfoFile();

        $version = (string) (config('azshrtr.release.version')
            ?: ($fromFile['version'] ?? null)
            ?: self::versionFromFile()
            ?: '0.0.1');

        return [
            'version' => $version,
            'build' => self::nullableString(config('azshrtr.release.build') ?: ($fromFile['build'] ?? null)),
            'commit' => self::nullableString(config('azshrtr.release.commit') ?: ($fromFile['commit'] ?? null)),
        ];
    }

    private static function versionFromFile(): ?string
    {
        $path = base_path('VERSION');

        if (! is_file($path)) {
            return null;
        }

        $version = trim((string) file_get_contents($path));

        return $version !== '' ? $version : null;
    }

    /**
     * @return array{version?: string, build?: string, commit?: string}|null
     */
    private static function fromBuildInfoFile(): ?array
    {
        $path = base_path('build-info.json');

        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string !== '' ? $string : null;
    }
}
