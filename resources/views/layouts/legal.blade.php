@extends('layouts.marketing')

@section('title', $legalMetaTitle)
@section('meta_description', $legalMetaDescription)
@section('og_title', $legalMetaTitle)
@section('og_description', $legalMetaDescription)
@section('canonical', $legalCanonical)

@section('content')
<section class="border-b border-white/10">
    <div class="mkt-shell py-12 sm:py-16">
        <p class="mkt-d-body text-sm">
            <a href="{{ route('home') }}" class="mkt-d-link">Home</a>
            <span class="mx-2" style="color:rgba(247,252,251,0.35)">/</span>
            <span>{{ $legalTitle }}</span>
        </p>
        <h1 class="mkt-d-title mt-4">
            {{ $legalTitle }}
        </h1>
        <p class="mkt-d-body mt-3">
            Last updated {{ $legalUpdated }} · {{ config('marketing.organization') }}
        </p>
    </div>
</section>

<section>
    <div class="mkt-shell py-12 sm:py-16 lg:flex lg:gap-14">
        <aside class="mb-10 w-full shrink-0 lg:mb-0 lg:w-52">
            <p class="mkt-label">Legal</p>
            <nav class="mt-3 flex flex-col gap-0.5" aria-label="Legal">
                @foreach ($legalNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="mkt-side-link {{ $legalSlug === $item['slug'] ? 'is-active' : '' }}"
                    >
                        {{ $item['title'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <article class="legal-prose min-w-0 flex-1 max-w-3xl">
            @yield('legal')
        </article>
    </div>
</section>
@endsection
