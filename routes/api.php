<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MfaController;
use App\Http\Controllers\Api\PasskeyController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\ApiRequestLogController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\DodoWebhookController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ImportExportController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\OverviewController;
use App\Http\Controllers\Api\V1\Product\LinkApiController;
use App\Http\Controllers\Api\V1\Product\MeController;
use App\Http\Controllers\Api\V1\QrController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::post('/webhooks/dodo', DodoWebhookController::class)
        ->name('api.v1.webhooks.dodo');

    Route::middleware('web')->group(function (): void {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/auth/email/verify', [AuthController::class, 'verifyEmail']);
        Route::post('/auth/mfa/challenge', [MfaController::class, 'challenge']);
        Route::post('/auth/email-otp/send', [AuthController::class, 'sendEmailOtp']);
        Route::post('/auth/email-otp/verify', [AuthController::class, 'verifyEmailOtp']);
        Route::post('/auth/passkeys/login/options', [AuthController::class, 'passkeyLoginOptions']);
        Route::post('/auth/passkeys/login/verify', [AuthController::class, 'passkeyLoginVerify']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::get('/auth/me', [AuthController::class, 'me']);
            Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
            Route::put('/auth/password', [AuthController::class, 'updatePassword']);
            Route::post('/auth/email/resend-confirmation', [AuthController::class, 'resendConfirmation']);

            Route::get('/auth/mfa', [MfaController::class, 'status']);
            Route::post('/auth/mfa/setup', [MfaController::class, 'beginSetup']);
            Route::post('/auth/mfa/confirm', [MfaController::class, 'confirmSetup']);
            Route::post('/auth/mfa/disable', [MfaController::class, 'disable']);
            Route::post('/auth/mfa/recovery-codes', [MfaController::class, 'regenerateRecoveryCodes']);

            Route::get('/auth/passkeys', [PasskeyController::class, 'index']);
            Route::post('/auth/passkeys/register/options', [PasskeyController::class, 'registerOptions']);
            Route::post('/auth/passkeys/register', [PasskeyController::class, 'register']);
            Route::delete('/auth/passkeys/{passkeyId}', [PasskeyController::class, 'destroy']);

            Route::post('/links/claim', [LinkController::class, 'claim']);

            Route::get('/organizations', [OrganizationController::class, 'index']);
            Route::post('/organizations', [OrganizationController::class, 'store']);

            Route::post('/invitations/accept', [MemberController::class, 'accept']);

            Route::prefix('organizations/{organizationId}')->group(function (): void {
                Route::get('/overview', OverviewController::class);
                Route::get('/analytics', AnalyticsController::class);

                Route::get('/members', [MemberController::class, 'index']);
                Route::get('/members/export', [MemberController::class, 'export']);
                Route::post('/members/invite', [MemberController::class, 'invite']);
                Route::post('/members/bulk-delete', [MemberController::class, 'destroyMany']);
                Route::get('/members/{memberId}', [MemberController::class, 'show']);
                Route::patch('/members/{memberId}', [MemberController::class, 'updateRole']);
                Route::delete('/members/{memberId}', [MemberController::class, 'destroy']);
                Route::delete('/invitations/{invitationId}', [MemberController::class, 'revokeInvitation']);

                Route::get('/links', [LinkController::class, 'index']);
                Route::get('/links/export', [LinkController::class, 'export']);
                Route::post('/links', [LinkController::class, 'store']);
                Route::post('/links/bulk-delete', [LinkController::class, 'destroyMany']);
                Route::get('/links/{linkId}', [LinkController::class, 'show']);
                Route::patch('/links/{linkId}', [LinkController::class, 'update']);
                Route::delete('/links/{linkId}', [LinkController::class, 'destroy']);

                Route::get('/qr', [QrController::class, 'index']);
                Route::get('/qr/export', [QrController::class, 'export']);
                Route::post('/qr', [QrController::class, 'store']);
                Route::post('/qr/bulk-delete', [QrController::class, 'destroyMany']);
                Route::get('/qr/{qrId}', [QrController::class, 'show']);
                Route::delete('/qr/{qrId}', [QrController::class, 'destroy']);
                Route::get('/qr/{qrId}/download', [QrController::class, 'downloadQr']);
                Route::get('/links/{linkId}/qr.svg', [QrController::class, 'download']);

                Route::get('/domains', [DomainController::class, 'index']);
                Route::get('/domains/export', [DomainController::class, 'export']);
                Route::post('/domains', [DomainController::class, 'store']);
                Route::post('/domains/bulk-delete', [DomainController::class, 'destroyMany']);
                Route::get('/domains/{domainId}', [DomainController::class, 'show']);
                Route::post('/domains/{domainId}/verify', [DomainController::class, 'verify']);
                Route::delete('/domains/{domainId}', [DomainController::class, 'destroy']);

                Route::get('/api-keys', [ApiKeyController::class, 'index']);
                Route::get('/api-keys/export', [ApiKeyController::class, 'export']);
                Route::post('/api-keys', [ApiKeyController::class, 'store']);
                Route::post('/api-keys/bulk-delete', [ApiKeyController::class, 'destroyMany']);
                Route::get('/api-keys/{apiKeyId}', [ApiKeyController::class, 'show']);
                Route::delete('/api-keys/{apiKeyId}', [ApiKeyController::class, 'destroy']);

                Route::get('/api-request-logs', [ApiRequestLogController::class, 'index']);
                Route::get('/api-request-logs/export', [ApiRequestLogController::class, 'export']);
                Route::get('/api-request-logs/{logId}', [ApiRequestLogController::class, 'show']);

                Route::get('/audit-logs', [AuditLogController::class, 'index']);
                Route::get('/audit-logs/export', [AuditLogController::class, 'export']);
                Route::get('/audit-logs/{logId}', [AuditLogController::class, 'show']);
                Route::get('/billing', [BillingController::class, 'show']);
                Route::get('/billing/invoices', [BillingController::class, 'invoices']);
                Route::post('/billing/checkout', [BillingController::class, 'checkout']);
                Route::post('/billing/checkout-return', [BillingController::class, 'checkoutReturn']);
                Route::post('/billing/cancel', [BillingController::class, 'cancel']);
                Route::post('/billing/resume', [BillingController::class, 'resume']);

                Route::post('/export', [ImportExportController::class, 'export']);
                Route::get('/exports/{exportId}/download', [ImportExportController::class, 'download']);
                Route::post('/import', [ImportExportController::class, 'import']);
            });
        });
    });

    // Product API (Bearer az_… keys)
    Route::get('/me', MeController::class)->middleware('api.key:links:read');
    Route::get('/links', [LinkApiController::class, 'index'])->middleware('api.key:links:read');
    Route::post('/links', [LinkApiController::class, 'store'])->middleware('api.key:links:write');
    Route::get('/links/{id}', [LinkApiController::class, 'show'])->middleware('api.key:links:read');
    Route::patch('/links/{id}', [LinkApiController::class, 'update'])->middleware('api.key:links:write');
    Route::delete('/links/{id}', [LinkApiController::class, 'destroy'])->middleware('api.key:links:write');
    Route::get('/links/{id}/clicks', [LinkApiController::class, 'clicks'])->middleware('api.key:analytics:read');
});
