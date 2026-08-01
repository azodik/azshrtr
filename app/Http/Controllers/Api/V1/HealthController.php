<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->checkDatabase();
        $ok = $database['ok'] === true;

        return response()->json([
            'ok' => $ok,
            'service' => 'azshrtr',
            'checks' => [
                'database' => $database,
            ],
        ], $ok ? 200 : 503);
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1 as ok');

            return [
                'ok' => true,
            ];
        } catch (Throwable $exception) {
            $payload = [
                'ok' => false,
            ];

            if (config('app.debug')) {
                $payload['error'] = $exception->getMessage();
            }

            return $payload;
        }
    }
}
