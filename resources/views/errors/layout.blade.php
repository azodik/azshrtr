@php
    $status = (int) ($status ?? 500);
    $title = $title ?? 'Something went wrong';
    $message = $message ?? 'Please try again in a moment.';
    $isConsole = request()->is('console', 'console/*');
    $homeUrl = $isConsole ? url('/console') : route('home');
    $homeLabel = $isConsole ? 'Back to console' : 'Back to azshrtr';
    $secondaryUrl = $isConsole ? route('home') : url('/console');
    $secondaryLabel = $isConsole ? 'Marketing site' : 'Open console';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B6E6E">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark">
    <title>{{ $status }} — {{ $title }} · azshrtr</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600|syne:600,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal: #0b6e6e;
            --teal-deep: #085454;
            --mint: #8fd4ce;
            --paper: #f7fcfb;
            --ink-soft: rgba(247, 252, 251, 0.72);
            --canvas: #0c1413;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", system-ui, sans-serif;
            color: var(--paper);
            background:
                radial-gradient(120% 80% at 10% -10%, rgba(143, 212, 206, 0.18), transparent 55%),
                linear-gradient(168deg, #043434 0%, var(--teal) 42%, #0a4f4f 72%, var(--canvas) 100%);
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            width: min(40rem, calc(100% - 2rem));
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 0;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 2.5rem;
        }
        .brand img {
            width: 1.75rem;
            height: 1.75rem;
            filter: brightness(0) invert(1);
        }
        .brand span {
            font-family: Syne, system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
        }
        .code {
            display: inline-block;
            font-family: Syne, system-ui, sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mint);
            margin-bottom: 0.85rem;
        }
        h1 {
            margin: 0 0 0.85rem;
            font-family: Syne, system-ui, sans-serif;
            font-size: clamp(1.85rem, 4vw, 2.6rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        p {
            margin: 0 0 2rem;
            max-width: 32rem;
            font-size: 1.05rem;
            line-height: 1.55;
            color: var(--ink-soft);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.65rem 1.15rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: var(--paper);
            color: var(--teal-deep);
        }
        .btn-primary:hover { background: #fff; }
        .btn-ghost {
            border: 1px solid rgba(247, 252, 251, 0.28);
            color: var(--paper);
        }
        .btn-ghost:hover { border-color: var(--mint); color: var(--mint); }
    </style>
</head>
<body>
    <div class="shell">
        <a class="brand" href="{{ $homeUrl }}" aria-label="azshrtr home">
            <img src="{{ asset('images/mark.svg') }}?v=2" alt="" width="28" height="28">
            <span>azshrtr</span>
        </a>
        <div class="code">Error {{ $status }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
            <a class="btn btn-ghost" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
        </div>
    </div>
</body>
</html>
