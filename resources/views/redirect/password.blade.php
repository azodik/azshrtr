<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password required — azshrtr</title>
    @vite(['resources/css/app.css'])
</head>
<body class="az-atmosphere flex min-h-screen items-center justify-center px-5">
    <form method="post" action="{{ url('/'.$code) }}" class="w-full max-w-sm space-y-4 rounded-lg border border-mist/70 bg-paper-elevated p-6">
        @csrf
        <h1 class="font-display text-xl font-semibold">Password required</h1>
        <p class="text-sm text-ink-soft/80">This short link is protected.</p>
        @if (!empty($error))
            <p class="text-sm text-danger">{{ $error }}</p>
        @endif
        <label class="block text-sm font-medium" for="password">Password</label>
        <input id="password" name="password" type="password" required class="w-full rounded-md border border-mist px-3 py-2">
        <button type="submit" class="mkt-btn-primary w-full">Continue</button>
    </form>
</body>
</html>
