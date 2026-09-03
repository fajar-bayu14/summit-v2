<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\ProcessWithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\EscrowService;
use App\Services\XenditDisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WithdrawalController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/withdrawals',
        summary: 'List all partner withdrawal requests (Admin)',
        tags: ['Withdrawal (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Withdrawals list fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WithdrawalResource')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Withdrawal::with(['mitra', 'approver'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $withdrawals = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar penarikan dana berhasil dimuat.',
            'data' => WithdrawalResource::collection($withdrawals)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/withdrawals/{id}/approve',
        summary: 'Approve and trigger Xendit Payout for a withdrawal request',
        tags: ['Withdrawal (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Withdrawal approved & payout triggered',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/WithdrawalResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid withdrawal state'),
        ]
    )]
    public function approve(
        Request $request,
        int $id,
        EscrowService $escrowService,
        XenditDisbursementService $disbursementService
    ): JsonResponse {
        $withdrawal = Withdrawal::with('mitra')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan dengan status pending yang dapat disetujui.',
            ], 422);
        }

        $withdrawal->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Trigger Xendit disbursement API
        $disbursementService->createDisbursement($withdrawal);

        return response()->json([
            'status' => 'success',
            'message' => 'Penarikan dana disetujui dan proses transfer Xendit telah dimulai.',
            'data' => new WithdrawalResource($withdrawal->fresh(['mitra', 'approver'])),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/withdrawals/{id}/reject',
        summary: 'Reject a withdrawal request and refund balance to partner',
        tags: ['Withdrawal (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan_penolakan'],
                properties: [
                    new OA\Property(property: 'alasan_penolakan', type: 'string', example: 'Data rekening bank tidak sesuai dengan dokumen legalitas mitra'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Withdrawal rejected successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/WithdrawalResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid withdrawal state'),
        ]
    )]
    public function reject(
        ProcessWithdrawalRequest $request,
        int $id,
        EscrowService $escrowService
    ): JsonResponse {
        $withdrawal = Withdrawal::with('mitra')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan dengan status pending yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validated();
        $withdrawal = $escrowService->rejectWithdrawal($withdrawal, $validated['alasan_penolakan']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan penarikan dana berhasil ditolak dan saldo dikembalikan ke mitra.',
            'data' => new WithdrawalResource($withdrawal->fresh(['mitra', 'approver'])),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/escrow/ledger',
        summary: 'Get consolidated escrow ledger across all partners (Admin)',
        tags: ['Withdrawal (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consolidated ledger fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletTransactionResource')),
                    ]
                )
            ),
        ]
    )]
    public function ledger(Request $request): JsonResponse
    {
        $query = WalletTransaction::with(['wallet.mitra', 'pesanan'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan konsolidasi ledger escrow berhasil dimuat.',
            'data' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }
}
