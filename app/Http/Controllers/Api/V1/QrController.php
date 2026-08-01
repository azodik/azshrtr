<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\DestroysMany;
use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Concerns\ResolvesListQuery;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\QrCode;
use App\Services\Qr\QrCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrController extends Controller
{
    use DestroysMany;
    use EnsuresOrganizationMembership;
    use ResolvesListQuery;

    public function __construct(private readonly QrCodeService $qr) {}

    public function index(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'size', 'content', 'format']);

        return response()->json(
            $this->filteredQuery($organization->id, $filters)
                ->with('link:id,code,title,destination_url')
                ->paginate($this->perPage($request)),
        );
    }

    public function export(Request $request, string $organizationId): StreamedResponse|JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $filters = $this->listFilters($request, ['created_at', 'size', 'content', 'format']);
        $format = $request->validate(['format' => ['sometimes', 'in:csv,json']])['format'] ?? 'csv';

        $rows = $this->filteredQuery($organization->id, $filters)
            ->with('link:id,code,title')
            ->limit(5000)
            ->get();

        if ($format === 'json') {
            return response()->json(['data' => $rows])->withHeaders([
                'Content-Disposition' => 'attachment; filename="qr-codes.json"',
            ]);
        }

        return $this->streamCsv(
            'qr-codes.csv',
            ['id', 'link_code', 'content', 'size', 'format', 'created_at'],
            $rows->map(fn (QrCode $qr) => [
                $qr->id,
                $qr->link?->code,
                $qr->content,
                $qr->size,
                $qr->format,
                optional($qr->created_at)?->toIso8601String(),
            ]),
        );
    }

    public function show(Request $request, string $organizationId, string $qrId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $qr = QrCode::query()
            ->with('link:id,code,title,destination_url')
            ->where('organization_id', $organization->id)
            ->whereKey($qrId)
            ->firstOrFail();

        $svg = $this->qr->renderSvg($qr->content, $qr->size);

        return response()->json([
            'qr' => $qr,
            'svg' => $svg,
        ]);
    }

    public function store(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $data = $request->validate([
            'link_id' => ['nullable', 'uuid', 'required_without:content'],
            'content' => ['nullable', 'string', 'max:2048', 'required_without:link_id'],
            'size' => ['sometimes', 'integer', 'min:64', 'max:1024'],
        ]);

        $size = (int) ($data['size'] ?? 256);

        if (! empty($data['link_id'])) {
            $link = Link::query()
                ->where('organization_id', $organization->id)
                ->whereKey($data['link_id'])
                ->firstOrFail();

            $qr = $this->qr->generateForLink(
                $link,
                $organization,
                $request->user(),
                $size,
            );
        } else {
            $qr = $this->qr->generateForContent(
                (string) $data['content'],
                $organization,
                $request->user(),
                $size,
            );
        }

        $svg = $this->qr->renderSvg($qr->content, $qr->size);

        return response()->json([
            'qr' => $qr,
            'svg' => $svg,
        ], 201);
    }

    public function destroy(Request $request, string $organizationId, string $qrId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $qr = QrCode::query()
            ->where('organization_id', $organization->id)
            ->whereKey($qrId)
            ->firstOrFail();

        $qr->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyMany(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $ids = $this->bulkIds($request);

        $deleted = QrCode::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function download(Request $request, string $organizationId, string $linkId): Response
    {
        $organization = $this->organization($request, $organizationId);
        $link = Link::query()->where('organization_id', $organization->id)->whereKey($linkId)->firstOrFail();
        $svg = $this->qr->renderSvg($link->shortUrl());

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$link->code.'.svg"',
        ]);
    }

    public function downloadQr(Request $request, string $organizationId, string $qrId): Response
    {
        $organization = $this->organization($request, $organizationId);
        $qr = QrCode::query()
            ->where('organization_id', $organization->id)
            ->whereKey($qrId)
            ->firstOrFail();

        $svg = $this->qr->renderSvg($qr->content, $qr->size);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.$qr->id.'.svg"',
        ]);
    }

    /**
     * @param  array{sort: string, direction: string, q: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<QrCode>
     */
    private function filteredQuery(string $organizationId, array $filters): Builder
    {
        $query = QrCode::query()->where('organization_id', $organizationId);

        if ($filters['q'] !== null) {
            $q = $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('content', 'like', '%'.$q.'%')
                    ->orWhereHas('link', function (Builder $linkQuery) use ($q): void {
                        $linkQuery->where('code', 'like', '%'.$q.'%')
                            ->orWhere('title', 'like', '%'.$q.'%')
                            ->orWhere('destination_url', 'like', '%'.$q.'%');
                    });
            });
        }

        return $this->applySortAndDates($query, $filters);
    }
}
