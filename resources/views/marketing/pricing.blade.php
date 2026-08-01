@extends('layouts.marketing')

@section('title', 'Pricing — azshrtr')
@section('meta_description', 'Free for 3,000 short links and 300 QR codes per month. Pro is $20/year for unlimited links, custom domains, and password protection.')

@section('content')
    <section class="mkt-shell py-14 sm:py-20">
        <p class="mkt-label">Pricing</p>
        <h1 class="mkt-d-title mt-4">Free to start. $20 a year to go Pro.</h1>
        <p class="mkt-d-lead mt-4">
            Start Free on Azshrtr Cloud. Upgrade to Pro when you need unlimited usage, custom domains, and password-protected links.
            Self-host with billing off for unlimited local use —
            <a href="{{ route('docs.show', ['page' => 'billing']) }}" class="mkt-d-link">how billing works</a>.
        </p>

        <div class="mt-14 grid grid-cols-1 border-y border-white/10 lg:grid-cols-2">
            <article class="flex min-w-0 flex-col border-b border-white/10 py-10 lg:border-b-0 lg:border-r lg:py-12 lg:pr-12">
                <h2 class="mkt-d-h3">Free</h2>
                <p class="mkt-d-price mt-4">$0</p>
                <p class="mkt-d-body mt-2" style="color:rgba(247,252,251,0.5)">No credit card</p>
                <ul class="mt-8 space-y-3 mkt-d-body">
                    <li class="mkt-d-feature">3,000 short URLs / month</li>
                    <li class="mkt-d-feature">300 QR generations / month</li>
                    <li class="mkt-d-feature">API within caps (2 keys)</li>
                    <li class="mkt-d-feature">Import / export</li>
                    <li class="mkt-d-feature">7-day audit · 30-day click retention</li>
                </ul>
                <div class="mt-10">
                    <a href="{{ url('/console/register?plan=free') }}" class="mkt-btn-outline-light">Get started Free</a>
                    <p class="mkt-d-body mt-3" style="color:rgba(247,252,251,0.5)">
                        Already have an account?
                        <a href="{{ url('/console/login?plan=free') }}" class="mkt-d-link">Sign in</a>
                    </p>
                </div>
            </article>

            <article class="flex min-w-0 flex-col py-10 lg:py-12 lg:pl-12" style="background:linear-gradient(180deg, rgba(143,212,206,0.06), transparent 55%)">
                <h2 class="mkt-d-h3" style="color:#8fd4ce">Pro</h2>
                <p class="mkt-d-price mt-4">
                    <span class="amount">$20</span><span class="per">/year</span>
                </p>
                <p class="mkt-d-body mt-2" style="color:rgba(247,252,251,0.5)">Billed yearly only</p>
                <ul class="mt-8 space-y-3 mkt-d-body">
                    <li class="mkt-d-feature">Unlimited short URLs &amp; QR</li>
                    <li class="mkt-d-feature">Custom domains + SSL</li>
                    <li class="mkt-d-feature">Password-protected links</li>
                    <li class="mkt-d-feature">20 API keys · higher limits</li>
                    <li class="mkt-d-feature">90-day audit · 365-day click retention</li>
                </ul>
                <div class="mt-10">
                    <a href="{{ url('/console/register?plan=pro') }}" class="mkt-btn-light">Get started Pro</a>
                    <p class="mkt-d-body mt-3" style="color:rgba(247,252,251,0.5)">
                        Already have an account?
                        <a href="{{ url('/console/login?plan=pro') }}" class="mkt-d-link">Sign in</a>
                    </p>
                </div>
            </article>
        </div>

        <div class="mt-20">
            <h2 class="mkt-d-title">Compare</h2>
            <p class="mkt-d-lead mt-2">
                Everything in Free, plus Pro unlocks domains, password links, and unlimited monthly usage.
            </p>

            <div class="mt-8 overflow-x-auto">
                <table class="mkt-compare min-w-[32rem]">
                    <thead>
                        <tr>
                            <th class="pl-0">Feature</th>
                            <th>Free</th>
                            <th class="pr-0">Pro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pl-0 font-medium">Price</td>
                            <td>$0</td>
                            <td class="pr-0">$20 / year</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">Short URLs / month</td>
                            <td>3,000</td>
                            <td class="pr-0">Unlimited</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">QR / month</td>
                            <td>300</td>
                            <td class="pr-0">Unlimited</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">Custom domains</td>
                            <td>—</td>
                            <td class="pr-0">Yes</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">Password links</td>
                            <td>—</td>
                            <td class="pr-0">Yes</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">API keys</td>
                            <td>2</td>
                            <td class="pr-0">20</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">Audit retention</td>
                            <td>7 days</td>
                            <td class="pr-0">90 days</td>
                        </tr>
                        <tr>
                            <td class="pl-0 font-medium">Click retention</td>
                            <td>30 days</td>
                            <td class="pr-0">365 days</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
