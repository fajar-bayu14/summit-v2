<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyXenditWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.xendit.callback_token');
        $providedToken = (string) $request->header('x-callback-token', '');

        if ($expectedToken === '') {
            Log::error('Xendit callback token is not configured in services.xendit.callback_token.');

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Konfigurasi webhook server belum lengkap.',
                'error_code' => 'ERR_SERVER_MISCONFIGURATION',
            ], 500);
        }

        if ($providedToken === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Otentikasi webhook gagal. Header x-callback-token tidak ditemukan.',
                'error_code' => 'ERR_UNAUTHORIZED_WEBHOOK',
            ], 401);
        }

        if (! hash_equals($expectedToken, $providedToken)) {
            Log::warning('Percobaan pemalsuan webhook Xendit terdeteksi.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Otentikasi webhook gagal. Token verifikasi tidak valid.',
                'error_code' => 'ERR_INVALID_WEBHOOK_TOKEN',
            ], 401);
        }

        return $next($request);
    }
}
