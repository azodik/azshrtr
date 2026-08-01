<?php

namespace App\Services\Links;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DestinationUrlValidator
{
    public function validate(string $url): string
    {
        $validator = Validator::make(
            ['url' => $url],
            ['url' => ['required', 'url', 'max:2048']],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $parsed = parse_url($url);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw ValidationException::withMessages([
                'url' => ['Only http and https destinations are allowed.'],
            ]);
        }

        return $url;
    }
}
