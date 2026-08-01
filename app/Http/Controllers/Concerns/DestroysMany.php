<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait DestroysMany
{
    /**
     * @return list<string>
     */
    protected function bulkIds(Request $request, int $max = 100): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.$max],
            'ids.*' => ['uuid'],
        ]);

        return array_values(array_unique($validated['ids']));
    }
}
