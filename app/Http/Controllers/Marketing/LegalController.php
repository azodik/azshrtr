<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegalController extends Controller
{
    /**
     * @return list<array{slug: string, title: string, route: string}>
     */
    public static function legalNav(): array
    {
        return [
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'route' => 'privacy'],
            ['slug' => 'terms', 'title' => 'Terms of Service', 'route' => 'terms'],
            ['slug' => 'cookies', 'title' => 'Cookie Policy', 'route' => 'cookies'],
        ];
    }

    /**
     * @return array<string, array{title: string, meta_title: string, description: string}>
     */
    public static function legalSeo(): array
    {
        return [
            'privacy' => [
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy — azshrtr',
                'description' => 'How Azodik Consulting Private Limited collects and uses information for Azshrtr and Azshrtr Cloud.',
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'meta_title' => 'Terms of Service — azshrtr',
                'description' => 'Terms for using Azshrtr websites, open-source software, and Azshrtr Cloud.',
            ],
            'cookies' => [
                'title' => 'Cookie Policy',
                'meta_title' => 'Cookie Policy — azshrtr',
                'description' => 'How Azshrtr uses cookies and similar technologies on marketing pages and Azshrtr Cloud.',
            ],
        ];
    }

    public function privacy(): View
    {
        return $this->legal('privacy');
    }

    public function terms(): View
    {
        return $this->legal('terms');
    }

    public function cookies(): View
    {
        return $this->legal('cookies');
    }

    private function legal(string $slug): View
    {
        $seo = self::legalSeo()[$slug] ?? null;
        if ($seo === null) {
            throw new NotFoundHttpException;
        }

        return view('marketing.legal.'.$slug, [
            'legalNav' => self::legalNav(),
            'legalSlug' => $slug,
            'legalTitle' => $seo['title'],
            'legalMetaTitle' => $seo['meta_title'],
            'legalMetaDescription' => $seo['description'],
            'legalCanonical' => route($slug),
            'legalUpdated' => 'August 1, 2026',
        ]);
    }
}
