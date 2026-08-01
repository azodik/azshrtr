<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\EnsuresOrganizationMembership;
use App\Http\Controllers\Controller;
use App\Models\LinkExport;
use App\Services\Links\LinkImportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportExportController extends Controller
{
    use EnsuresOrganizationMembership;

    public function __construct(
        private readonly LinkImportExportService $importExport,
    ) {}

    public function export(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $format = $request->validate(['format' => ['sometimes', 'in:json,csv']])['format'] ?? 'json';

        $result = $this->importExport->export($organization, $request->user(), $format);

        return response()->json($result);
    }

    public function download(Request $request, string $organizationId, string $exportId)
    {
        $organization = $this->organization($request, $organizationId);
        $export = LinkExport::query()
            ->where('organization_id', $organization->id)
            ->whereKey($exportId)
            ->firstOrFail();

        abort_unless(filled($export->path) && Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path);
    }

    public function import(Request $request, string $organizationId): JsonResponse
    {
        $organization = $this->organization($request, $organizationId);
        $data = $request->validate([
            'format' => ['required', 'in:json,csv'],
            'payload' => ['required', 'string'],
        ]);

        $import = $this->importExport->import(
            $organization,
            $request->user(),
            $data['format'],
            $data['payload'],
        );

        return response()->json(['import' => $import]);
    }
}
