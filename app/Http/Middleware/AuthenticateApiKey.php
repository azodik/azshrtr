<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\Usage\UsageTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiKeyService $keys,
        private readonly UsageTracker $usage,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Missing API key.'], 401);
        }

        $plain = trim(substr($header, 7));
        $apiKey = $this->keys->findByPlainText($plain);

        if ($apiKey === null) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        foreach ($scopes as $scope) {
            if ($scope !== '' && ! $apiKey->hasScope($scope)) {
                return response()->json(['message' => 'Insufficient API key scope.'], 403);
            }
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_organization', $apiKey->organization);

        $started = microtime(true);
        $response = $next($request);
        $latency = (int) ((microtime(true) - $started) * 1000);

        $this->usage->incrementApiCalls($apiKey->organization);

        ApiRequestLog::query()->create([
            'organization_id' => $apiKey->organization_id,
            'api_key_id' => $apiKey->id,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'latency_ms' => $latency,
            'created_at' => now(),
        ]);

        return $response;
    }
}
