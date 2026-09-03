<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class XenditDisbursementWebhookController extends Controller
{
    #[OA\Post(
        path: '/api/v1/payments/webhook/xendit-disbursement',
        summary: 'Handle Xendit Iris Disbursement callback (COMPLETED / FAILED)',
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
            new OA\Response(response: 404, description: 'Withdrawal record not found'),
        ]
    )]
    public function handle(Request $request, EscrowService $escrowService): JsonResponse
    {
        $payload = $request->input();
        $disbursementId = $payload['id'] ?? null;
        $externalId = $payload['external_id'] ?? null;

        $withdrawal = null;
        if ($externalId) {
            $withdrawalId = (int) str_replace('WD-', '', $externalId);
            $withdrawal = Withdrawal::find($withdrawalId);
        }
        if (! $withdrawal && $disbursementId) {
            $withdrawal = Withdrawal::where('disbursement_id', $disbursementId)->first();
        }

        if (! $withdrawal) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catatan penarikan dana tidak ditemukan.',
            ], 404);
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        if (in_array($status, ['COMPLETED', 'SUCCESS', 'DISBURSED'], true)) {
            $escrowService->settleWithdrawalSuccess($withdrawal, $disbursementId);
        } elseif ($status === 'FAILED') {
            $failureReason = $payload['failure_code'] ?? ($payload['failure_message'] ?? 'Transfer ditolak oleh bank tujuan.');
            $escrowService->handleWithdrawalFailure($withdrawal, $failureReason);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Disbursement webhook processed.',
        ]);
    }
}
