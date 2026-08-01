<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Enums\EmailOtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\EmailOtpService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\PasskeyService;
use App\Services\Auth\SignInNotifier;
use App\Services\OrganizationService;
use App\Support\SupportedLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizations,
        private readonly AuditLogger $audit,
        private readonly EmailVerificationService $emailVerification,
        private readonly EmailOtpService $emailOtp,
        private readonly PasskeyService $passkeys,
        private readonly SignInNotifier $signInNotifier,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'accepted_terms' => ['accepted'],
            'preferred_locale' => ['sometimes', 'nullable', 'string', Rule::in(SupportedLocale::ALL)],
        ], [
            'accepted_terms.accepted' => 'You must accept the Privacy Policy and Terms of Service.',
        ]);

        $locale = SupportedLocale::fromRequest($data['preferred_locale'] ?? null);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
            'email_verified_at' => null,
            'preferred_locale' => $locale,
        ]);

        $this->organizations->createForUser($user);
        $this->audit->log(AuditAction::UserRegistered, $user);
        $this->emailVerification->issue($user);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->authPayload($user->fresh() ?? $user), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
            'preferred_locale' => ['sometimes', 'nullable', 'string', Rule::in(SupportedLocale::ALL)],
        ]);

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            false,
        )) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->loadMissing('mfaSettings');

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        if ($user->mfaEnabled()) {
            Auth::logout();
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_pending_remember', (bool) ($credentials['remember'] ?? false));

            return response()->json([
                'mfa_required' => true,
                'message' => 'Enter your authenticator or recovery code to continue.',
            ]);
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();
        $this->maybePersistLocale($user, $request);
        $this->signInNotifier->recordSuccessfulLogin($user, $request);

        $this->audit->log(AuditAction::UserLoggedIn, $user);

        if ($user->email_verified_at === null) {
            $this->emailVerification->issue($user->fresh() ?? $user);
        }

        return response()->json($this->authPayload($user->fresh() ?? $user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->authPayload($user));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'preferred_locale' => ['sometimes', 'required', 'string', Rule::in(SupportedLocale::ALL)],
            'theme_preference' => ['sometimes', 'required', 'string', Rule::in(['light', 'dark', 'system'])],
        ]);

        $updates = [];
        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
        }
        if (array_key_exists('preferred_locale', $data)) {
            $updates['preferred_locale'] = SupportedLocale::normalize($data['preferred_locale']);
        }
        if (array_key_exists('theme_preference', $data)) {
            $updates['theme_preference'] = $data['theme_preference'];
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
            $this->audit->log(AuditAction::ProfileUpdated, $user, null, 'user', (string) $user->id);
        }

        return response()->json($this->authPayload($user->fresh() ?? $user));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill(['password' => $data['password']])->save();
        $this->audit->log(AuditAction::PasswordChanged, $user, null, 'user', (string) $user->id);

        return response()->json(['message' => 'Password updated.']);
    }

    public function sendEmailOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'preferred_locale' => ['sometimes', 'nullable', 'string', Rule::in(SupportedLocale::ALL)],
        ]);

        $this->emailOtp->send(
            $data['email'],
            EmailOtpPurpose::Login,
            $data['preferred_locale'] ?? SupportedLocale::fromRequest(),
        );

        return response()->json([
            'message' => 'If an account exists for that email, a sign-in code was sent.',
        ]);
    }

    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->emailOtp->verify($data['email'], $data['code'], EmailOtpPurpose::Login);

        if ($user->mfaEnabled()) {
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_pending_remember', false);

            return response()->json([
                'mfa_required' => true,
                'message' => 'Enter your authenticator or recovery code to continue.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->maybePersistLocale($user, $request);
        $this->signInNotifier->recordSuccessfulLogin($user, $request);
        $this->audit->log(AuditAction::UserLoggedIn, $user);

        return response()->json($this->authPayload($user->fresh() ?? $user));
    }

    public function passkeyLoginOptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['sometimes', 'nullable', 'email'],
        ]);

        $rpId = $request->getHost();

        return response()->json(
            $this->passkeys->authenticationOptions($rpId, $data['email'] ?? null),
        );
    }

    public function passkeyLoginVerify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
        ]);

        $user = $this->passkeys->authenticate($data['credential'], $request->getHost());

        if ($user->mfaEnabled()) {
            // Passkey already proves possession; still honor MFA if enabled.
            Auth::logout();
            $request->session()->put('mfa_pending_user_id', $user->id);
            $request->session()->put('mfa_pending_remember', false);

            return response()->json([
                'mfa_required' => true,
                'message' => 'Enter your authenticator or recovery code to continue.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->maybePersistLocale($user, $request);
        $this->signInNotifier->recordSuccessfulLogin($user, $request);
        $this->audit->log(AuditAction::UserLoggedIn, $user);

        return response()->json($this->authPayload($user->fresh() ?? $user));
    }

    public function resendConfirmation(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email is already verified.',
                'csrf_token' => csrf_token(),
            ]);
        }

        $this->emailVerification->issue($user);

        return response()->json([
            'message' => 'Confirmation email sent.',
            'csrf_token' => csrf_token(),
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        if ($request->filled('token')) {
            $user = $this->emailVerification->verifyToken($request->string('token')->toString());
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Email verified.',
                ...$this->authPayload($user->fresh() ?? $user),
            ]);
        }

        if ($request->user() === null) {
            throw ValidationException::withMessages([
                'code' => ['Sign in to verify with a code, or use the email link.'],
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        /** @var User $authUser */
        $authUser = $request->user();
        $user = $this->emailVerification->verifyCode($authUser, $validated['code']);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Email verified.',
            ...$this->authPayload($user->fresh() ?? $user),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'preferred_locale' => ['sometimes', 'nullable', 'string', Rule::in(SupportedLocale::ALL)],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if ($user !== null) {
            $user->forceFill([
                'preferred_locale' => SupportedLocale::fromRequest($data['preferred_locale'] ?? null),
            ])->save();
        }

        Password::sendResetLink(['email' => $data['email']]);

        return response()->json(['message' => 'If that email exists, a reset link was sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password reset successful.']);
    }

    private function maybePersistLocale(User $user, Request $request): void
    {
        $locale = $request->input('preferred_locale');
        if (! is_string($locale) || $locale === '') {
            $locale = SupportedLocale::fromRequest();
            // Only persist Accept-Language when the user still has the default.
            if ($user->preferred_locale !== null && $user->preferred_locale !== SupportedLocale::DEFAULT) {
                return;
            }
        }

        $normalized = SupportedLocale::normalize($locale);
        if ($user->preferred_locale !== $normalized) {
            $user->forceFill(['preferred_locale' => $normalized])->save();
        }
    }

    /**
     * @return array{user: array<string, mixed>, csrf_token: string}
     */
    public function authPayload(User $user): array
    {
        return [
            'user' => $this->serializeUser($user),
            'csrf_token' => csrf_token(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeUser(User $user): array
    {
        $user->load([
            'mfaSettings',
            'organizations' => fn ($q) => $q->orderBy('name'),
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'theme_preference' => $user->theme_preference,
            'preferred_locale' => $user->preferred_locale,
            'mfa_enabled' => $user->mfaEnabled(),
            'organizations' => $user->organizations->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'slug' => $org->slug,
                'role' => $org->pivot->role instanceof \BackedEnum
                    ? $org->pivot->role->value
                    : $org->pivot->role,
            ])->values(),
        ];
    }
}
