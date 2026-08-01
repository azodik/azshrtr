<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Controller;
use App\Models\LinkClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    use EnsuresOrganizationMembership;

    public function __invoke(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $request->validate([
            'days' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);
        $days = (int) ($filters['days'] ?? 30);
        $since = now()->subDays($days);

        $base = LinkClick::query()
            ->where('organization_id', $organization->id)
            ->where('clicked_at', '>=', $since);

        $total = (clone $base)->count();

        $byCountry = (clone $base)
            ->select('country', DB::raw('count(*) as total'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'country' => $row->country,
                'total' => (int) $row->total,
            ]);

        $byBrowser = (clone $base)
            ->select('browser', DB::raw('count(*) as total'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'browser' => $row->browser,
                'total' => (int) $row->total,
            ]);

        $byDevice = (clone $base)
            ->select('device_bucket', DB::raw('count(*) as total'))
            ->whereNotNull('device_bucket')
            ->groupBy('device_bucket')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'device' => $row->device_bucket,
                'total' => (int) $row->total,
            ]);

        $byCity = (clone $base)
            ->select('city', 'country', DB::raw('count(*) as total'))
            ->whereNotNull('city')
            ->groupBy('city', 'country')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'city' => $row->city,
                'country' => $row->country,
                'total' => (int) $row->total,
            ]);

        $daily = (clone $base)
            ->select(DB::raw('DATE(clicked_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
            ]);

        $recent = (clone $base)
            ->with(['link:id,code,destination_url,title'])
            ->latest('clicked_at')
            ->limit(25)
            ->get()
            ->map(fn (LinkClick $click) => [
                'id' => $click->id,
                'clicked_at' => $click->clicked_at?->toIso8601String(),
                'country' => $click->country,
                'region' => $click->region,
                'city' => $click->city,
                'browser' => $click->browser,
                'device_bucket' => $click->device_bucket,
                'referrer' => $click->referrer,
                'link' => $click->link ? [
                    'id' => $click->link->id,
                    'code' => $click->link->code,
                    'title' => $click->link->title,
                    'destination_url' => $click->link->destination_url,
                ] : null,
            ]);

        return response()->json([
            'days' => $days,
            'total_clicks' => $total,
            'by_country' => $byCountry,
            'by_city' => $byCity,
            'by_browser' => $byBrowser,
            'by_device' => $byDevice,
            'daily' => $daily,
            'recent' => $recent,
            'source' => 'Location from edge geo signals; browser and device from User-Agent.',
        ]);
    }
}
