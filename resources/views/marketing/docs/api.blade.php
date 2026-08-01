@extends('layouts.docs')

@section('title', 'API — azshrtr docs')
@section('meta_description', 'Azshrtr REST API v1 with Bearer API keys, OpenAPI, and Stoplight explorer.')

@section('docs')
    <p>
        Azshrtr ships a versioned JSON API under <code class="rounded bg-fog px-1.5 py-0.5 text-sm">/api/v1</code>.
        Organization data is accessed with an
        <strong>API key</strong>:
    </p>
    <pre class="mt-3 overflow-x-auto rounded-lg bg-ink px-4 py-3 text-sm text-paper"><code>Authorization: Bearer az_live_…
# or
Authorization: Bearer az_test_…</code></pre>
    <p class="mt-3">
        Create keys in the console under <strong>API keys</strong>.
        The console UI login is separate from this machine API.
    </p>

    <h2 class="mt-10">OpenAPI &amp; explorer</h2>
    <ul class="mt-3 list-disc space-y-2 pl-5">
        <li>
            Spec:
            <a href="{{ asset('openapi.yaml') }}" class="text-teal underline-offset-2 hover:underline">/openapi.yaml</a>
        </li>
        <li>
            Interactive explorer (Stoplight Elements) — authenticate with your API key:
            <a href="{{ route('docs.show', ['page' => 'api-explorer']) }}" class="text-teal underline-offset-2 hover:underline">/docs/api-explorer</a>
        </li>
    </ul>

    <h2 class="mt-10">Product endpoints</h2>
    <ul class="mt-3 list-disc space-y-2 pl-5">
        <li><code class="text-sm">GET /api/v1/me</code> — org, plan, usage</li>
        <li><code class="text-sm">POST /api/v1/links</code> — create short link</li>
        <li><code class="text-sm">GET /api/v1/links</code> — list</li>
        <li><code class="text-sm">GET /api/v1/links/{id}</code> — show</li>
        <li><code class="text-sm">PATCH /api/v1/links/{id}</code> — update</li>
        <li><code class="text-sm">DELETE /api/v1/links/{id}</code> — delete</li>
        <li><code class="text-sm">GET /api/v1/links/{id}/clicks</code> — analytics</li>
        <li><code class="text-sm">POST /api/v1/webhooks/dodo</code> — billing webhooks</li>
        <li><code class="text-sm">GET /api/v1/health</code> — liveness</li>
    </ul>

    <h2 class="mt-10">Scopes</h2>
    <p class="mt-3">
        Keys can include
        <code class="text-sm">links:read</code>,
        <code class="text-sm">links:write</code>,
        <code class="text-sm">qr:write</code>,
        <code class="text-sm">domains:read</code>,
        <code class="text-sm">analytics:read</code>.
    </p>

    <h2 class="mt-10">Example</h2>
    <pre class="mt-3 overflow-x-auto rounded-lg bg-ink px-4 py-3 text-sm text-paper"><code>curl -X POST "{{ rtrim(config('app.url'), '/') }}/api/v1/links" \
  -H "Authorization: Bearer az_test_…" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"destination_url":"https://azshrtr.com","title":"Launch"}'</code></pre>

    <h2 class="mt-10">Org-scoped routes</h2>
    <p class="mt-3">
        Additional organization resources are documented in OpenAPI under
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">/api/v1/organizations/{organizationId}/…</code>
        (links, QR, domains, members, API keys, billing, import/export, audit).
        Use a Bearer API key in the explorer.
    </p>

    <h2 class="mt-10">Tests</h2>
    <p class="mt-3">
        Feature and integration coverage for the API lives in
        <code class="text-sm">tests/Feature/</code>
        (<code class="text-sm">ProductApiTest</code>,
        <code class="text-sm">ConsoleApiIntegrationTest</code>,
        <code class="text-sm">AuthSessionApiTest</code>, and related).
        Run with <code class="text-sm">composer test</code>.
    </p>
@endsection
