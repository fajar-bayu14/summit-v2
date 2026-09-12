<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessXenditDisbursementWebhookJob;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class XenditDisbursementWebhookController extends Controller
{
    #[OA\Post(
        path: '/api/v1/payments/webhook/xendit-disbursement',
        summary: 'Handle Xendit Iris Disbursement callback (COMPLETED / FAILED / REVERSED)',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: 'disb_12345'),
                    new OA\Property(property: 'external_id', type: 'string', example: 'WD-1'),
                    new OA\Property(property: 'status', type: 'string', example: 'COMPLETED'),
                    new OA\Property(property: 'amount', type: 'number', example: 500000),
                    new OA\Property(property: 'failure_code', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Disbursement webhook acknowledged'),
            new OA\Response(response: 401, description: 'Unauthorized: Invalid or missing token'),
        ]
    )]
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->input();
        $disbursementId = (string) ($payload['id'] ?? '');
        $externalId = (string) ($payload['external_id'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');

        $headerId = $request->header('webhook-id');
        $eventId = (string) ($headerId ?: hash('sha256', "xendit:disbursement:{$disbursementId}:{$status}:{$amount}:{$externalId}"));

        try {
            $event = WebhookEvent::create([
                'provider' => 'xendit',
                'event_id' => $eventId,
                'event_type' => isset($payload['status']) ? 'disbursement.'.strtolower((string) $payload['status']) : 'disbursement.unknown',
                'resource_type' => 'withdrawal',
                'resource_id' => $externalId !== '' ? $externalId : ($disbursementId !== '' ? $disbursementId : null),
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

        ProcessXenditDisbursementWebhookJob::dispatch($event->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Disbursement webhook processed.',
        ]);
    }
}
