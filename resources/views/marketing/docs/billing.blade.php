@extends('layouts.docs')

@section('title', 'Billing — azshrtr docs')
@section('meta_description', 'Azshrtr Free vs Pro plans, Dodo Payments setup, and self-host unlimited mode.')

@section('docs')
    <p>
        Billing is <strong class="font-medium text-ink">optional</strong>. Self-host with
        <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_BILLING_ENABLED=false</code>
        for unlimited local use. Azshrtr Cloud turns billing on and meters Free plan caps.
    </p>

    <h2 class="font-display pt-4 text-xl font-semibold text-ink">Plans</h2>
    <div class="overflow-x-auto rounded-md border border-mist/60">
        <table class="w-full text-left text-sm">
            <thead class="bg-fog/60 text-ink">
                <tr>
                    <th class="px-3 py-2 font-semibold">Feature</th>
                    <th class="px-3 py-2 font-semibold">Free</th>
                    <th class="px-3 py-2 font-semibold">Pro</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-mist/50">
                <tr>
                    <td class="px-3 py-2">Price</td>
                    <td class="px-3 py-2">$0</td>
                    <td class="px-3 py-2">$20 / year only</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Short URLs / month</td>
                    <td class="px-3 py-2">3,000</td>
                    <td class="px-3 py-2">Unlimited</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">QR generations / month</td>
                    <td class="px-3 py-2">300</td>
                    <td class="px-3 py-2">Unlimited</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Custom domains</td>
                    <td class="px-3 py-2">—</td>
                    <td class="px-3 py-2">Yes</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Password-protected links</td>
                    <td class="px-3 py-2">—</td>
                    <td class="px-3 py-2">Yes</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">API keys</td>
                    <td class="px-3 py-2">2 (within caps)</td>
                    <td class="px-3 py-2">20</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Audit retention</td>
                    <td class="px-3 py-2">7 days</td>
                    <td class="px-3 py-2">90 days</td>
                </tr>
                <tr>
                    <td class="px-3 py-2">Click analytics retention</td>
                    <td class="px-3 py-2">30 days</td>
                    <td class="px-3 py-2">365 days</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 class="font-display pt-6 text-xl font-semibold text-ink">Setup (Cloud)</h2>
    <ol class="list-decimal space-y-2 pl-5">
        <li>Set <code class="rounded bg-fog px-1.5 py-0.5 text-sm">AZSHRTR_BILLING_ENABLED=true</code> and <code class="rounded bg-fog px-1.5 py-0.5 text-sm">DODO_PAYMENTS_API_KEY</code>.</li>
        <li>Run <code class="rounded bg-fog px-1.5 py-0.5 text-sm">php artisan setup:dodo --webhook=https://your-host/api/v1/webhooks/dodo</code>.</li>
        <li>Confirm <code class="rounded bg-fog px-1.5 py-0.5 text-sm">DODO_PRODUCT_PRO</code> and webhook secret are written to <code class="rounded bg-fog px-1.5 py-0.5 text-sm">.env</code>.</li>
        <li>Owners upgrade from Console → Billing (hosted checkout). Cancel keeps Pro until period end, then Free caps apply — data is not deleted.</li>
    </ol>

    <p class="pt-2">
        Env reference: <a href="{{ route('docs.show', ['page' => 'configuration']) }}" class="mkt-link">Configuration</a>.
    </p>
@endsection
