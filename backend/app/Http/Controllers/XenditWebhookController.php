<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessXenditPaymentWebhookJob;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class XenditWebhookController extends Controller
{
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
            new OA\Response(response: 401, description: 'Unauthorized: Invalid or missing token'),
        ]
    )]
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->input();
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $externalId = (string) ($payload['external_id'] ?? ($data['external_id'] ?? ($data['reference_id'] ?? '')));
        $rawIdentifier = (string) ($payload['id'] ?? ($data['id'] ?? ''));
        $status = (string) ($payload['status'] ?? ($data['status'] ?? ''));
        $amount = (string) ($payload['paid_amount'] ?? ($payload['amount'] ?? ($data['paid_amount'] ?? ($data['amount'] ?? ''))));
        $paymentId = (string) ($payload['payment_id'] ?? ($data['payment_id'] ?? ''));

        $headerId = $request->header('webhook-id');
        $eventId = (string) ($headerId ?: hash('sha256', "xendit:invoice:{$rawIdentifier}:{$status}:{$amount}:{$paymentId}"));

        $eventType = isset($payload['event'])
            ? (string) $payload['event']
            : ($status !== '' ? 'invoice.'.strtolower($status) : 'invoice.unknown');

        try {
            $event = WebhookEvent::create([
                'provider' => 'xendit',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'resource_type' => 'order',
                'resource_id' => $externalId !== '' ? $externalId : null,
                'payload' => $payload,
                'ip_address' => $request->ip(),
                'verification_status' => 'verified',
                'processing_status' => 'pending',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'status' => 'success',
                'message' => 'Event webhook duplikat telah diterima sebelumnya.',
            ], 200);
        }

        ProcessXenditPaymentWebhookJob::dispatch($event->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook diterima.',
        ]);
    }
}
