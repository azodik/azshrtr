<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Links\LinkService;
use App\Services\Qr\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShortenController extends Controller
{
    public function __construct(
        private readonly LinkService $links,
        private readonly QrCodeService $qr,
    ) {}

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $link = $this->links->createAnonymous($data['url'], $request->ip());
        $this->qr->generateForLink($link, null);
        $svg = $this->qr->renderSvg($link->shortUrl());

        $payload = [
            'short_url' => $link->shortUrl(),
            'destination_url' => $link->destination_url,
            'expires_at' => $link->expires_at?->toIso8601String(),
            'claim_url' => url('/console/claim/'.$link->claim_token),
            'qr_svg' => $svg,
        ];

        if ($request->expectsJson()) {
            return response()->json(['shorten' => $payload]);
        }

        return redirect()
            ->route('home')
            ->with('shorten', $payload);
    }
}
