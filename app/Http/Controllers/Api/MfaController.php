<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\MfaService;
use App\Services\Auth\SignInNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function __construct(
        private readonly MfaService $mfa,
        private readonly AuthController $auth,
        private readonly SignInNotifier $signInNotifier,
    ) {}

    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->loadMissing('mfaSettings');

        return response()->json([
            'mfa_enabled' => $user->mfaEnabled(),
            'mfa_confirmed_at' => $user->mfaSettings?->confirmed_at?->toIso8601String(),
            'recovery_codes_remaining' => $this->mfa->remainingRecoveryCodeCount($user),
        ]);
    }

    public function beginSetup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->mfa->beginSetup($user));
    }

    public function confirmSetup(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $codes = $this->mfa->confirmSetup($user->fresh() ?? $user, $data['code']);

        return response()->json([
            'message' => 'Authenticator MFA enabled.',
            'recovery_codes' => $codes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $this->mfa->disable($user, $data['code']);

        return response()->json(['message' => 'Authenticator MFA disabled.']);
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $codes = $this->mfa->regenerateRecoveryCodes($user, $data['code']);

        return response()->json([
            'message' => 'Recovery codes regenerated.',
            'recovery_codes' => $codes,
        ]);
    }

    public function challenge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('mfa_pending_user_id');
        if (! is_int($userId) && ! is_numeric($userId)) {
            throw ValidationException::withMessages([
                'code' => ['MFA challenge expired. Sign in again.'],
            ]);
        }

        $user = User::query()->find($userId);
        if ($user === null || ! $user->is_active) {
            throw ValidationException::withMessages([
                'code' => ['MFA challenge expired. Sign in again.'],
            ]);
        }

        if (! $this->mfa->verify($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authenticator or recovery code.'],
            ]);
        }

        $request->session()->forget('mfa_pending_user_id');
        Auth::login($user, (bool) $request->session()->pull('mfa_pending_remember', false));
        $request->session()->regenerate();
        $this->signInNotifier->recordSuccessfulLogin($user, $request);

        return response()->json([
            'message' => 'Signed in.',
            ...$this->auth->authPayload($user->fresh() ?? $user),
        ]);
    }
}
