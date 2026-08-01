<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasskeyController extends Controller
{
    public function __construct(private readonly PasskeyService $passkeys) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'passkeys' => $user->passkeys()
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'credential_id', 'last_used_at', 'created_at']),
        ]);
    }

    public function registerOptions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->passkeys->registrationOptions($user, $request->getHost()));
    }

    public function register(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
        ]);

        $passkey = $this->passkeys->register(
            $user,
            $data['credential'],
            $request->getHost(),
            $data['name'] ?? null,
        );

        return response()->json([
            'passkey' => $passkey->only(['id', 'name', 'created_at']),
        ], 201);
    }

    public function destroy(Request $request, string $passkeyId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->passkeys->delete($user, $passkeyId);

        return response()->json(['ok' => true]);
    }
}
