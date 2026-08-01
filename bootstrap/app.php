<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureJsonRequest;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Console API routes already use the `web` middleware group for sessions/CSRF.
        // Do not also enable statefulApi() — that nests EncryptCookies/StartSession a
        // second time and each response Set-Cookie replaces the authenticated session.

        $middleware->validateCsrfTokens(except: [
            'api/v1/webhooks/*',
            'api/v1/public/*',
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->api(prepend: [
            EnsureJsonRequest::class,
        ]);

        $middleware->alias([
            'api.key' => AuthenticateApiKey::class,
        ]);

        // Match Authzio: leave XSRF-TOKEN unencrypted so the SPA can prefer the
        // compact meta CSRF token (avoids large X-XSRF-TOKEN headers).
        $middleware->encryptCookies(except: [
            'XSRF-TOKEN',
        ]);

        $middleware->redirectGuestsTo('/console/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = trim($exception->getMessage());
                $isFrameworkRouteMiss = $message === ''
                    || $message === 'Not Found'
                    || str_starts_with($message, 'The route ');

                return response()->json([
                    'message' => $isFrameworkRouteMiss
                        ? 'The requested resource was not found.'
                        : $message,
                ], 404);
            }

            return null;
        });

        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'The request body is too large.',
                ], 413);
            }

            return null;
        });
    })->create();
