<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocsController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const PAGES = [
        'install' => 'Install',
        'configuration' => 'Configuration',
        'api' => 'API',
        'api-explorer' => 'API explorer',
        'custom-domains' => 'Custom domains',
        'billing' => 'Billing',
        'shared-hosting' => 'Shared hosting',
        'faq' => 'FAQ',
    ];

    /**
     * @return array<string, string>
     */
    public static function pages(): array
    {
        return self::PAGES;
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('docs.show', ['page' => 'install']);
    }

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view('marketing.docs.'.$page, [
            'docsPages' => self::PAGES,
            'currentPage' => $page,
            'pageTitle' => self::PAGES[$page],
        ]);
    }
}
