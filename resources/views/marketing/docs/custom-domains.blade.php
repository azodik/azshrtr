@extends('layouts.docs')

@section('title', 'Custom domains — azshrtr docs')
@section('meta_description', 'Add Pro custom short domains with Cloudflare for SaaS or DNS TXT verification.')

@section('docs')
    <p>
        Pro workspaces can serve short links on their own hostname (e.g. <code class="rounded bg-fog px-1.5 py-0.5 text-sm">go.acme.com/abc</code>).
        Primary links always work on your root host (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_DOMAIN_ROOT</code>).
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Flow</h2>
    <ol class="list-decimal space-y-2 pl-5">
        <li>In Console → Domains, add a hostname.</li>
        <li>
            If Cloudflare for SaaS is enabled, Azshrtr creates a Custom Hostname and shows CNAME + ownership / SSL TXT records.
            CNAME target defaults to <code class="rounded bg-fog px-1.5 py-0.5 text-sm">customers.azshrtr.com</code>
            (<code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_CUSTOM_DOMAIN_CNAME_TARGET</code>).
        </li>
        <li>Publish those DNS records at your registrar.</li>
        <li>Click <strong class="font-medium text-ink">Verify</strong>. When hostname + SSL are active, the domain is marked verified.</li>
        <li>Create links with that domain selected — redirects resolve by <code class="rounded bg-fog px-1.5 py-0.5 text-sm">Host</code> header.</li>
    </ol>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Without Cloudflare</h2>
    <p>
        Set <code class="rounded bg-fog px-1.5 py-0.5 text-sm">CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=false</code>.
        Verify uses a live DNS TXT check on the host (or <code class="rounded bg-fog px-1.5 py-0.5 text-sm">_azshrtr-challenge.&lt;host&gt;</code>)
        when <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_DOMAIN_DNS_VERIFY=true</code>.
        You still need TLS in front of the app (reverse proxy / Cloudflare proxy / etc.).
    </p>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Env</h2>
    <pre class="overflow-x-auto rounded-md bg-ink/[0.04] p-4 text-sm text-ink"><code>AZSHRTR_DOMAIN_ROOT=azshrtr.com
AZSHRTR_CUSTOM_DOMAIN_CNAME_TARGET=customers.azshrtr.com
CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=true
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ZONE_ID=
CLOUDFLARE_CUSTOM_HOSTNAME_SSL_METHOD=txt</code></pre>
    <p>
        API token needs Zone → SSL and Certificates → Edit (Custom Hostnames).
        Pending hostnames are polled hourly by <code class="rounded bg-fog px-1.5 py-0.5 text-sm">domains:sync-cloudflare</code>.
    </p>
@endsection
