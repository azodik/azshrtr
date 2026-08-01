<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B6E6E">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'azshrtr')</title>
    <meta name="description" content="@yield('meta_description', 'Azshrtr API explorer')">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    @stack('head')
</head>
<body class="m-0 min-h-screen bg-white text-neutral-900 antialiased">
    @yield('content')
</body>
</html>
