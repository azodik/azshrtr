@extends('layouts.marketing')

@section('content')
    <div class="mkt-shell grid grid-cols-1 gap-10 py-12 lg:grid-cols-[220px_1fr] lg:py-16">
        <aside class="min-w-0">
            <p class="mkt-label">Docs</p>
            <nav class="mt-4 flex flex-col gap-0.5" aria-label="Documentation">
                @foreach ($docsPages as $slug => $label)
                    <a
                        href="{{ route('docs.show', ['page' => $slug]) }}"
                        class="mkt-side-link {{ $currentPage === $slug ? 'is-active' : '' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <article class="min-w-0 max-w-3xl">
            <h1 class="mkt-d-title">{{ $pageTitle }}</h1>
            <div class="prose-az mt-8">
                @yield('docs')
            </div>
        </article>
    </div>
@endsection
