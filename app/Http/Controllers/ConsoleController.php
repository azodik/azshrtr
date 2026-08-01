<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BuildInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsoleController extends Controller
{
    public function show(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $theme = $user?->theme_preference;
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = null;
        }

        return view('console', [
            'buildInfo' => BuildInfo::toArray(),
            'themePreference' => $theme,
        ]);
    }
}
