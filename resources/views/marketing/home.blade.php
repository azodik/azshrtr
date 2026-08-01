@extends('layouts.marketing')

@section('title', 'azshrtr — Short links. Claim or expire.')
@section('meta_description', 'Paste a URL. Get a short link and QR. Claim it into an account or let it expire in 30 minutes. Open-source, self-hostable.')

@php
    /** @var array{short_url: string, destination_url: string, expires_at: ?string, claim_url: string, qr_svg: string}|null $shorten */
    $shorten = session('shorten');
    $demoHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'azshrtr.com';
    $ttl = (int) config('azshrtr.guest_link_ttl_minutes');
@endphp

@section('content')
        {{-- Hero: one full-viewport composition — brand, tool, proof --}}
        <section class="mkt-hero relative isolate overflow-hidden">
            <div class="mkt-hero-grid absolute inset-0" aria-hidden="true"></div>
            <div class="mkt-hero-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>

            <div class="mkt-shell mkt-hero-inner relative">
                <div class="mkt-hero-main">
                    <div class="az-hero-motion flex items-center justify-between gap-4">
                        <p class="mkt-hero-eyebrow">Open-source URL shortener</p>
                        <p class="mkt-hero-eyebrow hidden sm:block">MIT <span class="sep">·</span> Self-host or Cloud</p>
                    </div>

                    <div>
                        <p class="az-hero-motion mkt-hero-brand">azshrtr<span class="dot">.</span></p>
                        <h1 class="az-hero-motion-delay mkt-hero-title mt-5">
                            Short links. Claim or expire.
                        </h1>
                        <p class="az-hero-motion-delay mkt-hero-lead mt-4">
                            Paste a URL, get a short link and a QR — no account needed.
                            Claim it into a workspace to keep it forever, or let it expire.
                        </p>
                    </div>

                    <form
                        id="hero-shorten"
                        class="az-shortener-motion w-full max-w-2xl"
                        action="{{ route('shorten') }}"
                        method="post"
                        aria-label="Shorten a URL"
                        data-shorten-form
                    >
                        @csrf
                        <label for="destination" class="sr-only">Destination URL</label>
                        <div class="mkt-cmdbar">
                            <input
                                id="destination"
                                name="url"
                                type="url"
                                required
                                value="{{ old('url', $shorten['destination_url'] ?? '') }}"
                                placeholder="https://azshrtr.com/very/long/path"
                                autocomplete="url"
                                class="mkt-cmdbar-input"
                            >
                            <button type="submit" class="mkt-cmdbar-submit" data-shorten-submit>Shorten</button>
                        </div>
                        <p
                            id="shorten-error"
                            class="mkt-hero-note mt-3 {{ $errors->has('url') ? '' : 'hidden' }}"
                            role="alert"
                            data-shorten-error
                        >{{ $errors->first('url') }}</p>
                        <p class="mkt-hero-note mt-3">
                            Expires in {{ $ttl }} minutes unless claimed.
                            <a href="{{ route('console', ['any' => 'login']) }}">Sign in to keep forever</a>
                        </p>
                    </form>

                    <div
                        id="shorten-result"
                        class="az-shortener-motion grid w-full max-w-2xl gap-5 border-t border-white/15 pt-7 sm:grid-cols-[7.5rem_1fr] sm:items-end {{ $shorten ? '' : 'hidden' }}"
                        data-shorten-result
                        data-expires-at="{{ $shorten['expires_at'] ?? '' }}"
                    >
                        <div class="w-[7.5rem] bg-paper-elevated p-2 text-ink">
                            <div class="mkt-qr-box" data-shorten-qr>{!! $shorten['qr_svg'] ?? '' !!}</div>
                        </div>
                        <div class="min-w-0">
                            <a
                                id="result-url"
                                href="{{ $shorten['short_url'] ?? '#' }}"
                                class="block font-display text-xl font-semibold tracking-tight break-all text-paper-elevated underline-offset-4 hover:underline sm:text-2xl"
                                target="_blank"
                                rel="noopener"
                                data-shorten-url
                            >{{ $shorten['short_url'] ?? '' }}</a>
                            <p id="result-countdown" class="mkt-hero-note mt-2" data-shorten-countdown></p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    id="copy-btn"
                                    class="inline-flex items-center justify-center rounded-[var(--radius-control)] bg-paper-elevated px-4 py-2.5 text-sm font-semibold text-teal-deep transition-colors hover:bg-fog"
                                    data-copy="{{ $shorten['short_url'] ?? '' }}"
                                    data-shorten-copy
                                >Copy</button>
                                <a
                                    href="{{ $shorten['claim_url'] ?? '#' }}"
                                    class="inline-flex items-center justify-center rounded-[var(--radius-control)] border border-white/40 px-4 py-2.5 text-sm font-semibold text-paper-elevated transition-colors hover:bg-white/10"
                                    data-shorten-claim
                                >Claim this link</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mkt-hero-strip" aria-label="Highlights">
                    <p class="mkt-hero-strip-item"><span>01</span> — {{ $ttl }}-min guest links</p>
                    <p class="mkt-hero-strip-item"><span>02</span> — QR with every link</p>
                    <p class="mkt-hero-strip-item"><span>03</span> — REST API &amp; keys</p>
                    <p class="mkt-hero-strip-item"><span>04</span> — MIT self-host</p>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section class="border-t border-white/10">
            <div class="mkt-shell py-20 sm:py-28">
                <p class="mkt-label">How it works</p>
                <h2 class="mkt-d-title mt-4">Three steps. No ceremony.</h2>

                <div class="mt-14 grid grid-cols-1 gap-10 border-t border-white/10 pt-12 sm:grid-cols-3 sm:gap-8">
                    <div>
                        <p class="mkt-d-num">01</p>
                        <h3 class="mkt-d-h3 mt-4">Paste</h3>
                        <p class="mkt-d-body mt-2">
                            Drop any long URL into the box above. No account, no email, no settings to tune.
                        </p>
                    </div>
                    <div class="sm:border-l sm:border-white/10 sm:pl-8">
                        <p class="mkt-d-num">02</p>
                        <h3 class="mkt-d-h3 mt-4">Share</h3>
                        <p class="mkt-d-body mt-2">
                            Get a short link and a matching QR instantly. Copy it, print it, ship it anywhere.
                        </p>
                    </div>
                    <div class="sm:border-l sm:border-white/10 sm:pl-8">
                        <p class="mkt-d-num">03</p>
                        <h3 class="mkt-d-h3 mt-4">Claim or expire</h3>
                        <p class="mkt-d-body mt-2">
                            Guest links live for {{ $ttl }} minutes. Sign in to claim them into a workspace and keep them forever.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Product: QR + domains --}}
        <section class="border-t border-white/10">
            <div class="mkt-shell grid grid-cols-1 items-center gap-14 py-20 sm:py-28 lg:grid-cols-2 lg:gap-20">
                <div class="min-w-0">
                    <p class="mkt-label">The product</p>
                    <h2 class="mkt-d-title mt-4">QR built in. Your domain when you need it.</h2>
                    <p class="mkt-d-lead mt-4">
                        Every short link can ship a QR code out of the box. Pro unlocks custom domains with
                        automatic SSL and password-protected destinations for a branded edge.
                    </p>

                    <dl class="mt-10 divide-y divide-white/10 border-y border-white/10">
                        <div class="py-5">
                            <dt class="mkt-d-h3 text-base">QR with every link</dt>
                            <dd class="mkt-d-body mt-1">SVG or PNG, generated on demand — no third-party QR service.</dd>
                        </div>
                        <div class="py-5">
                            <dt class="mkt-d-h3 text-base">Custom domains + SSL</dt>
                            <dd class="mkt-d-body mt-1">Point your own domain, certificates are issued and renewed for you.</dd>
                        </div>
                        <div class="py-5">
                            <dt class="mkt-d-h3 text-base">Password-protected links</dt>
                            <dd class="mkt-d-body mt-1">Gate a destination behind a password when the audience is private.</dd>
                        </div>
                    </dl>

                    <a href="{{ route('pricing') }}" class="mkt-d-link mt-8 text-base">See Free vs Pro</a>
                </div>

                <div class="mkt-linkcard w-full max-w-xl p-6 sm:p-8" aria-hidden="true">
                    <div class="flex items-start justify-between gap-5 sm:gap-6">
                        <div class="min-w-0">
                            <p class="font-display text-lg font-semibold tracking-tight break-all sm:text-2xl" style="color:#f7fcfb">
                                {{ $demoHost }}/r/<span style="color:#8fd4ce">launch</span>
                            </p>
                            <p class="mt-2 truncate font-mono text-xs" style="color:rgba(247,252,251,0.45)">
                                → https://azshrtr.com/blog/2026/announcing-the-new-release
                            </p>
                        </div>
                        <div class="w-24 shrink-0 border border-white/15 bg-paper-elevated p-1.5 text-ink sm:w-28">
                            <div class="mkt-qr-box">{!! $demoQrSvg !!}</div>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-white/10 pt-4 text-xs" style="color:rgba(247,252,251,0.55)">
                        <span><span class="font-semibold" style="color:#f7fcfb">1,248</span> clicks</span>
                        <span>QR · SVG</span>
                        <span>Expires {{ $ttl }}:00 unless claimed</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Console walkthrough --}}
        <section class="border-t border-white/10">
            <div class="mkt-shell py-20 sm:py-28">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="mkt-label">Console</p>
                    <h2 class="mkt-d-title mt-4">Links, QR, domains, and API — one workspace.</h2>
                    <p class="mkt-d-lead mt-4 mx-auto">
                        Overview, analytics, keys, billing, and light/dark themes. Same product you self-host.
                    </p>
                </div>
                <figure class="mx-auto mt-12 max-w-5xl overflow-hidden border border-white/10 bg-teal-deep/40 shadow-[0_24px_80px_rgba(0,0,0,0.35)]">
                    <img
                        src="{{ asset('images/demo/console-tour-light.gif') }}"
                        alt="Azshrtr console walkthrough in light mode"
                        width="960"
                        height="600"
                        class="block h-auto w-full"
                        loading="lazy"
                    >
                </figure>
                <p class="mt-4 text-center text-sm" style="color:rgba(247,252,251,0.5)">
                    <a href="{{ asset('images/demo/console-tour-dark.gif') }}" class="mkt-d-link">Dark mode walkthrough</a>
                    ·
                    <a href="{{ route('console', ['any' => 'login']) }}" class="mkt-d-link">Open console</a>
                </p>
            </div>
        </section>

        {{-- Pricing teaser --}}
        <section class="border-t border-white/10">
            <div class="mkt-shell py-20 sm:py-28">
                <div class="flex flex-wrap items-end justify-between gap-6">
                    <div class="min-w-0 flex-1">
                        <p class="mkt-label">Pricing</p>
                        <h2 class="mkt-d-title mt-4">Free to start. $20 a year to go Pro.</h2>
                    </div>
                    <a href="{{ route('pricing') }}" class="mkt-d-link shrink-0 text-base">Full comparison</a>
                </div>

                <div class="mt-12 grid grid-cols-1 border-y border-white/10 sm:grid-cols-2">
                    <div class="min-w-0 border-b border-white/10 py-10 sm:border-b-0 sm:border-r sm:py-12 sm:pr-10 lg:pr-14">
                        <h3 class="mkt-d-h3">Free</h3>
                        <p class="mkt-d-price mt-4">$0</p>
                        <p class="mkt-d-body mt-2" style="color:rgba(247,252,251,0.5)">No credit card · start in seconds</p>
                        <ul class="mt-8 space-y-3 mkt-d-body">
                            <li class="mkt-d-feature">3,000 short URLs / month</li>
                            <li class="mkt-d-feature">300 QR generations / month</li>
                            <li class="mkt-d-feature">API access with 2 keys</li>
                        </ul>
                        <a href="{{ url('/console/register?plan=free') }}" class="mkt-btn-outline-light mt-9 w-full sm:w-auto">Start Free</a>
                    </div>
                    <div class="min-w-0 py-10 sm:py-12 sm:pl-10 lg:pl-14" style="background:linear-gradient(180deg, rgba(143,212,206,0.06), transparent 55%)">
                        <h3 class="mkt-d-h3" style="color:#8fd4ce">Pro</h3>
                        <p class="mkt-d-price mt-4">
                            <span class="amount">$20</span><span class="per">/year</span>
                        </p>
                        <p class="mkt-d-body mt-2" style="color:rgba(247,252,251,0.5)">Billed yearly · cancel anytime</p>
                        <ul class="mt-8 space-y-3 mkt-d-body">
                            <li class="mkt-d-feature">Unlimited short URLs &amp; QR</li>
                            <li class="mkt-d-feature">Custom domains + SSL, password links</li>
                            <li class="mkt-d-feature">20 API keys · longer retention</li>
                        </ul>
                        <a href="{{ url('/console/register?plan=pro') }}" class="mkt-btn-light mt-9 w-full sm:w-auto">Start Pro</a>
                    </div>
                </div>
                <p class="mkt-d-body mt-6" style="color:rgba(247,252,251,0.5)">
                    Self-hosting? Turn billing off for unlimited local use —
                    <a href="{{ route('docs.show', ['page' => 'billing']) }}" class="mkt-d-link">how billing works</a>.
                </p>
            </div>
        </section>

        {{-- Open source --}}
        <section class="border-t border-white/10">
            <div class="mkt-shell grid grid-cols-1 items-center gap-14 py-20 sm:py-28 lg:grid-cols-2 lg:gap-20">
                <div class="min-w-0">
                    <p class="mkt-label">Open source</p>
                    <h2 class="mkt-d-title mt-4">Run it yourself. Same product.</h2>
                    <p class="mkt-d-lead mt-4">
                        azshrtr is MIT-licensed. Host it on your own stack with MariaDB and a one-minute cron —
                        or use Azshrtr Cloud and never think about it.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('docs.show', ['page' => 'install']) }}" class="mkt-btn-light">Install docs</a>
                        <a href="{{ config('marketing.github') }}" class="mkt-btn-outline-light" rel="noopener noreferrer" target="_blank">GitHub</a>
                    </div>
                </div>

                <div class="mkt-terminal w-full max-w-2xl p-5 leading-loose sm:p-6" aria-label="Install commands">
                    <p><span class="prompt">$</span> git clone https://github.com/azodik/azshrtr.git</p>
                    <p><span class="prompt">$</span> cd azshrtr &amp;&amp; composer install</p>
                    <p><span class="prompt">$</span> php artisan azshrtr:setup</p>
                    <p class="pt-2" style="color:rgba(247,252,251,0.5)"># keep links expiring on schedule</p>
                    <p><span class="prompt">*</span> * * * * php artisan schedule:run</p>
                </div>
            </div>
        </section>

@endsection
