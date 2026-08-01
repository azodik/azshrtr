<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        return view('marketing.pricing');
    }
}
