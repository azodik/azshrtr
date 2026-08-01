<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B6E6E">
    <title>Azshrtr Console</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}?v=2">
    <script>
        (function () {
            try {
                var serverTheme = @json($themePreference ?? null);
                var theme = serverTheme || localStorage.getItem('azshrtr-theme') || 'system';
                if (serverTheme) {
                    localStorage.setItem('azshrtr-theme', serverTheme);
                }
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark = theme === 'dark' || (theme === 'system' && prefersDark);
                var root = document.documentElement;
                root.classList.toggle('dark', dark);
                root.style.colorScheme = dark ? 'dark' : 'light';
            } catch (e) {}
        })();
    </script>
    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/console/main.tsx'])
    <style>
        .az-boot-splash{display:flex;min-height:100vh;min-height:100dvh;align-items:center;justify-content:center;background:var(--color-paper,#e8f3f1);color:var(--color-ink,#121816)}
        html.dark .az-boot-splash{background:var(--color-paper,#0c1413)}
        .az-boot-splash__mark{position:relative;display:grid;place-items:center;width:5.5rem;height:5.5rem}
        .az-boot-splash__ring{position:absolute;inset:0;border-radius:9999px;border:2.5px solid color-mix(in oklab,#0b6e6e 22%,transparent);border-top-color:#0b6e6e;animation:az-boot-spin .85s linear infinite}
        html.dark .az-boot-splash__ring{border-color:color-mix(in oklab,#2ab5b5 22%,transparent);border-top-color:#2ab5b5}
        .az-boot-splash__mark img{width:2.5rem;height:2.5rem;display:block}
        @keyframes az-boot-spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body class="min-h-screen bg-paper text-ink antialiased font-sans">
    <script>
        window.__AZSHRTR__ = @json($buildInfo);
    </script>
    <div id="console-root">
        <div class="az-boot-splash" role="status" aria-live="polite" aria-label="Loading">
            <div class="az-boot-splash__mark">
                <span class="az-boot-splash__ring" aria-hidden="true"></span>
                <img src="{{ asset('images/mark.svg') }}?v=2" alt="" width="40" height="40">
            </div>
        </div>
    </div>
</body>
</html>

