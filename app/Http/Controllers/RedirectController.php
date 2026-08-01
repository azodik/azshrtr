<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\Domains\PlatformDomain;
use App\Services\Links\ClickRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function __construct(
        private readonly ClickRecorder $clicks,
        private readonly PlatformDomain $platformDomain,
    ) {}

    public function __invoke(Request $request, string $code): RedirectResponse|View
    {
        $host = strtolower($request->getHost());
        $platformHost = $this->platformDomain->hostname();

        if ($host === $platformHost || in_array($host, ['localhost', '127.0.0.1'], true)) {
            $domain = $this->platformDomain->resolve();
        } else {
            $domain = $this->platformDomain->findByHostname($host);
            if ($domain === null || (! $domain->is_system && ! $domain->isVerified())) {
                abort(404);
            }
        }

        $link = Link::query()
            ->where('domain_id', $domain->id)
            ->where('code', $code)
            ->where('is_disabled', false)
            ->first();

        if ($link === null || $link->isExpired()) {
            abort(404);
        }

        if ($link->isPasswordProtected()) {
            if ($request->isMethod('post')) {
                $password = (string) $request->input('password');
                if (! Hash::check($password, (string) $link->password_hash)) {
                    return view('redirect.password', [
                        'code' => $code,
                        'error' => 'Incorrect password.',
                    ]);
                }
            } else {
                return view('redirect.password', ['code' => $code, 'error' => null]);
            }
        }

        $this->clicks->record($link, $request);

        return redirect()->away($link->destination_url, 302);
    }
}
