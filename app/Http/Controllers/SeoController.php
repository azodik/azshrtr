<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Marketing\DocsController;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $base = rtrim((string) config('marketing.url'), '/');

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Allow: /docs',
            'Allow: /pricing',
            'Allow: /privacy',
            'Allow: /terms',
            'Allow: /cookies',
            'Disallow: /console',
            'Disallow: /api',
            'Disallow: /storage',
            '',
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function sitemap(): Response
    {
        $base = rtrim((string) config('marketing.url'), '/');
        $now = Carbon::now()->toAtomString();

        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base.'/pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $base.'/docs', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $base.'/privacy', 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => $base.'/terms', 'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => $base.'/cookies', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach (array_keys(DocsController::pages()) as $slug) {
            $urls[] = [
                'loc' => $base.'/docs/'.$slug,
                'priority' => '0.7',
                'changefreq' => 'monthly',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";
            $xml .= '    <lastmod>'.$now."</lastmod>\n";
            $xml .= '    <changefreq>'.$url['changefreq']."</changefreq>\n";
            $xml .= '    <priority>'.$url['priority']."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
