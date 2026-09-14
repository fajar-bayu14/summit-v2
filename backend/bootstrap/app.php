<?php

use App\Http\Middleware\EnsureKycVerified;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ProcessHttpOnlyCookie;
use App\Http\Middleware\VerifyXenditWebhook;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ForceJsonResponse::class);

        $middleware->prependToGroup('api', AddQueuedCookiesToResponse::class);
        $middleware->prependToGroup('api', ProcessHttpOnlyCookie::class);
        $middleware->prependToGroup('api', EncryptCookies::class);

        $middleware->alias([
            'role' => EnsureRole::class,
            'kyc.verified' => EnsureKycVerified::class,
            'xendit.webhook' => VerifyXenditWebhook::class,
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('orders:expire-pending')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/documentation*', 'docs*')) {
                return false;
            }

            return true;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            if ($e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan (404).',
                    'error_code' => 'ERR_MODEL_NOT_FOUND',
                ], 404);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Endpoint API tidak ditemukan (404).',
                'error_code' => 'ERR_NOT_FOUND',
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => "Method HTTP {$request->method()} tidak diizinkan untuk endpoint ini (405). Pastikan menggunakan method yang benar.",
                'error_code' => 'ERR_METHOD_NOT_ALLOWED',
            ], 405);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak (403). Anda tidak memiliki izin untuk mengakses resource ini.',
                'error_code' => 'ERR_FORBIDDEN',
            ], 403);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak (403). Tindakan ini tidak diizinkan untuk role Anda.',
                'error_code' => 'ERR_UNAUTHORIZED_ACTION',
            ], 403);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Tidak diautentikasi (401). Silakan login atau sertakan Bearer Token yang valid.',
                'error_code' => 'ERR_UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Data yang dikirimkan tidak valid.',
                'error_code' => 'ERR_VALIDATION_FAILED',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            \Illuminate\Support\Facades\Log::error('Unhandled Exception: '.$e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->is('api/documentation*', 'docs*')) {
                return null;
            }

            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Terjadi kesalahan pada server.',
                'error_code' => 'ERR_INTERNAL_SERVER',
                'error_details' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'debug' => $e->getTraceAsString(),
            ], $statusCode);
        });
    })->create();
