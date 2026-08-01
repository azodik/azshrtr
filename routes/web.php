<?php

use App\Http\Controllers\ConsoleController;
use App\Http\Controllers\Marketing\DocsController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\Marketing\LegalController;
use App\Http\Controllers\Marketing\PricingController;
use App\Http\Controllers\Marketing\ShortenController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/shorten', ShortenController::class)
    ->middleware('throttle:20,1')
    ->name('shorten');
Route::get('/pricing', PricingController::class)->name('pricing');

Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/cookies', [LegalController::class, 'cookies'])->name('cookies');

// Back-compat for earlier /legal/* links.
Route::redirect('/legal/privacy', '/privacy');
Route::redirect('/legal/terms', '/terms');

Route::prefix('docs')->name('docs.')->group(function (): void {
    Route::get('/', [DocsController::class, 'index'])->name('index');
    Route::get('/{page}', [DocsController::class, 'show'])->name('show');
});

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/openapi.yaml', function () {
    $path = public_path('openapi.yaml');
    abort_unless(is_file($path), 404);

    return response(
        (string) file_get_contents($path),
        200,
        [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ],
    );
})->name('openapi');

Route::get('/console/{any?}', [ConsoleController::class, 'show'])
    ->where('any', '.*')
    ->name('console');

// Short-link redirects — registered last so they never swallow app routes.
Route::match(['get', 'post'], '/{code}', RedirectController::class)
    ->where('code', '[A-Za-z0-9_-]{3,64}')
    ->name('redirect');
