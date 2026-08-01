<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesPagination
{
    protected function perPage(Request $request, int $default = 25, int $max = 100): int
    {
        $value = $request->integer('per_page', $default);

        if ($value < 1) {
            return $default;
        }

        return min($max, $value);
    }
}
