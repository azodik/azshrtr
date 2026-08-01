<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B6E6E">
    @php
        $isHome = request()->routeIs('home');
    @endphp
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')">
    <meta name="author" content="{{ config('marketing.organization') }}">
    <meta name="keywords" content="{{ implode(', ', config('marketing.keywords', [])) }}">
    <meta name="application-name" content="Azshrtr">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    @php
        $defaultTitle = 'azshrtr — Short links. Claim or expire.';
        $defaultDescription = 'Open-source URL shortener and QR platform. Shorten anonymously, claim later, or self-host forever. Free & Pro on Azshrtr Cloud.';
        $pageTitle = trim($__env->yieldContent('title', $defaultTitle));
        $pageDescription = trim($__env->yieldContent('meta_description', $defaultDescription));
        $canonical = trim($__env->yieldContent('canonical', url()->current()));
        $ogTitle = trim($__env->yieldContent('og_title', $pageTitle));
        $ogDescription = trim($__env->yieldContent('og_description', $pageDescription));
        $ogImage = trim($__env->yieldContent('og_image', asset(ltrim((string) config('marketing.og_image'), '/'))));
        $twitterImage = trim($__env->yieldContent('twitter_image', asset(ltrim((string) config('marketing.twitter_image', config('marketing.og_image')), '/'))));
        $ogImageAlt = trim($__env->yieldContent('og_image_alt', 'azshrtr — short links, claim or expire'));
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    @if (filled(config('marketing.google_site_verification')))
        <meta name="google-site-verification" content="{{ config('marketing.google_site_verification') }}">
    @endif

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="{{ config('marketing.locale') }}">
    <meta property="og:site_name" content="Azshrtr">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:width" content="{{ config('marketing.og_image_width') }}">
    <meta property="og:image:height" content="{{ config('marketing.og_image_height') }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">

    <meta name="twitter:card" content="summary_large_image">
    @if (filled(config('marketing.twitter')))
        <meta name="twitter:site" content="{{ config('marketing.twitter') }}">
    @endif
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $twitterImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=2">
    <meta name="msapplication-TileColor" content="#0B6E6E">

    @if (filled(config('marketing.gtm_id')))
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ config('marketing.gtm_id') }}');
        </script>
    @elseif (filled(config('marketing.ga4_id')))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('marketing.ga4_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('marketing.ga4_id') }}');
        </script>
    @endif

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="mkt-home-body mkt-home-canvas min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-teal focus:px-3 focus:py-2 focus:text-paper-elevated">
        Skip to content
    </a>

    <header class="{{ $isHome ? 'absolute inset-x-0 top-0 z-30' : 'relative z-20 border-b border-white/10' }}">
        <div class="mkt-shell flex items-center justify-between gap-4 py-4 sm:py-5">
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-2.5" aria-label="azshrtr home">
                <img
                    src="{{ asset('images/mark.svg') }}?v=2"
                    alt=""
                    width="28"
                    height="28"
                    class="h-7 w-7 brightness-0 invert"
                >
                @unless ($isHome)
                    <span class="font-display text-[1.35rem] font-semibold tracking-tight text-paper-elevated transition-colors group-hover:text-mint">azshrtr</span>
                @endunless
            </a>

            <nav class="hidden items-center gap-0.5 md:flex" aria-label="Primary">
                <a href="{{ route('pricing') }}" class="mkt-nav-link">Pricing</a>
                <a href="{{ route('docs.index') }}" class="mkt-nav-link">Docs</a>
                <a href="{{ route('console') }}" class="mkt-nav-link">Console</a>
                <a href="{{ config('marketing.sponsor') }}" class="mkt-nav-link" rel="noopener noreferrer" target="_blank">Sponsor</a>
                <a href="{{ route('console', ['any' => 'login']) }}" class="mkt-nav-cta ml-2">Sign in</a>
            </nav>

            <button
                type="button"
                class="mkt-nav-link md:hidden"
                data-nav-toggle
                aria-expanded="false"
                aria-controls="mobile-nav"
            >
                Menu
            </button>
        </div>

        <div id="mobile-nav" class="mkt-shell hidden border-t border-paper-elevated/20 bg-teal-deep/95 py-3 backdrop-blur md:hidden" data-nav-panel data-open="false">
            <nav class="flex flex-col gap-0.5" aria-label="Mobile">
                <a href="{{ route('pricing') }}" class="mkt-nav-link justify-start">Pricing</a>
                <a href="{{ route('docs.index') }}" class="mkt-nav-link justify-start">Docs</a>
                <a href="{{ route('console') }}" class="mkt-nav-link justify-start">Console</a>
                <a href="{{ config('marketing.sponsor') }}" class="mkt-nav-link justify-start" rel="noopener noreferrer" target="_blank">Sponsor</a>
                <a href="{{ route('console', ['any' => 'login']) }}" class="mkt-nav-cta mt-2">Sign in</a>
            </nav>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <footer class="mkt-footer-dark">
        <div class="mkt-shell flex flex-col gap-10 py-12 sm:py-14 md:flex-row md:items-end md:justify-between">
            <div class="max-w-sm">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                    <img src="{{ asset('images/mark.svg') }}?v=2" alt="" width="22" height="22" class="h-5.5 w-5.5 brightness-0 invert">
                    <span class="font-display text-lg font-semibold tracking-tight">azshrtr</span>
                </a>
                <p class="mt-4 text-sm leading-relaxed">
                    Open-source URL shortener and QR platform. Self-host or use Azshrtr Cloud.
                </p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm" aria-label="Footer">
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('docs.index') }}">Docs</a>
                <a href="{{ route('console') }}">Console</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ config('marketing.github') }}" rel="noopener noreferrer" target="_blank">GitHub</a>
            </nav>
        </div>

        <div class="border-t">
            <div class="mkt-shell flex flex-col gap-3 py-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs">
                    &copy; {{ date('Y') }}
                    <a
                        href="{{ config('marketing.organization_url') }}"
                        rel="noopener noreferrer"
                        target="_blank"
                    >{{ config('marketing.organization') }}</a>
                    · MIT
                </p>
                <div class="flex items-center gap-1" aria-label="Social">
                    <a href="{{ config('marketing.github') }}" class="mkt-social" aria-label="azshrtr on GitHub" rel="noopener noreferrer" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.28-.01-1.03-.02-2.02-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.33-1.76-1.33-1.76-1.09-.74.08-.73.08-.73 1.2.09 1.84 1.24 1.84 1.24 1.07 1.83 2.8 1.3 3.49.99.11-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.52.12-3.17 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.29-1.55 3.3-1.23 3.3-1.23.66 1.65.24 2.87.12 3.17.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.62-5.49 5.92.43.37.81 1.1.81 2.22 0 1.6-.01 2.89-.01 3.28 0 .32.22.7.83.58C20.56 21.8 24 17.3 24 12 24 5.37 18.63 0 12 0Z"/>
                        </svg>
                    </a>
                    <a href="{{ config('marketing.instagram') }}" class="mkt-social" aria-label="Azodik on Instagram" rel="noopener noreferrer" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069ZM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0Zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324ZM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881Z"/>
                        </svg>
                    </a>
                    <a href="{{ config('marketing.linkedin') }}" class="mkt-social" aria-label="Azodik on LinkedIn" rel="noopener noreferrer" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286ZM5.337 7.433a2.062 2.062 0 1 1-.004-4.125 2.062 2.062 0 0 1 .004 4.125ZM7.119 20.452H3.555V9h3.564v11.452ZM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003Z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
