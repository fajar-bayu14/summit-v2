<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookLog;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class XenditWebhookController extends Controller
{
    /**
     * Inject the order service.
     */
    public function __construct(
        protected PesananService $pesananService
    ) {}

    #[OA\Post(
        path: '/api/v1/payments/webhook/xendit',
        summary: 'Handle Xendit invoice callback (PAID / EXPIRED)',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: 'inv_abc123'),
                    new OA\Property(property: 'external_id', type: 'string', example: 'INV/20260808/ABC123'),
                    new OA\Property(property: 'status', type: 'string', example: 'PAID'),
                    new OA\Property(property: 'amount', type: 'number', example: 95000),
                    new OA\Property(property: 'paid_amount', type: 'number', example: 95000),
                    new OA\Property(property: 'payment_channel', type: 'string', example: 'QRIS'),
                    new OA\Property(property: 'payment_method', type: 'string', example: 'QR_CODE'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Webhook acknowledged'),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->input();
        $externalId = (string) ($payload['external_id'] ?? '');

        $pesanan = $externalId !== ''
            ? Pesanan::where('invoice', $externalId)->with(['pembayaran', 'details.produk', 'details.produk.tiket'])->first()
            : null;

        $log = PaymentWebhookLog::create([
            'pesanan_id' => $pesanan?->id,
            'pembayaran_id' => $pesanan?->pembayaran?->id,
            'provider' => 'xendit',
            'event' => isset($payload['status']) ? 'invoice.'.strtolower($payload['status']) : null,
            'external_id' => $externalId !== '' ? $externalId : null,
            'status_raw' => $payload['status'] ?? null,
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'is_valid' => true,
        ]);

        if (! $pesanan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice tidak ditemukan.',
            ], 404);
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        if ($status === 'PAID') {
            $this->pesananService->markPaid($pesanan, $payload);
        } elseif ($status === 'EXPIRED') {
            $this->pesananService->markExpired($pesanan);
        } else {
            $log->update(['error_message' => "Status webhook tidak ditangani: {$status}."]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook diterima.',
        ]);
    }
}
