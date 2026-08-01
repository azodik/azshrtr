<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Qr\QrCodeService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qr,
    ) {}

    public function __invoke(): View
    {
        return view('marketing.home', [
            'demoQrSvg' => $this->qr->renderSvg((string) config('app.url'), 220, 1),
        ]);
    }
}
