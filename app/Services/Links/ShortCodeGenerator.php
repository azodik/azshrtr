<?php

namespace App\Services\Links;

use App\Models\Link;
use Illuminate\Support\Str;

class ShortCodeGenerator
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(string $domainId, int $length = 7): string
    {
        for ($attempt = 0; $attempt < 12; $attempt++) {
            $code = $this->random($length);

            $exists = Link::query()
                ->where('domain_id', $domainId)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        return $this->random($length + 2);
    }

    private function random(int $length): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out !== '' ? $out : Str::lower(Str::random($length));
    }
}
