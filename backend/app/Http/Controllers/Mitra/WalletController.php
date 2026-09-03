<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWithdrawalRequest;
use App\Http\Resources\MitraWalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

class WalletController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/wallet',
        summary: 'Get partner escrow wallet balance summary',
        tags: ['Wallet (Mitra)'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet summary fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MitraWalletResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function summary(Request $request, EscrowService $escrowService): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $wallet = $escrowService->getOrCreateWalletWithLock($mitra);

        return response()->json([
            'status' => 'success',
            'message' => 'Ringkasan saldo dompet berhasil dimuat.',
            'data' => new MitraWalletResource($wallet->load('mitra')),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/wallet/ledger',
        summary: 'Get partner escrow ledger transactions history',
        tags: ['Wallet (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ledger history fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletTransactionResource')),
                    ]
                )
            ),
        ]
    )]
    public function ledger(Request $request, EscrowService $escrowService): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $wallet = $escrowService->getOrCreateWalletWithLock($mitra);
        $query = $wallet->transactions()->with('pesanan')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $transactions = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Mutasi transaksi dompet berhasil dimuat.',
            'data' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/withdrawals',
        summary: 'Request payout/withdrawal from available balance',
        tags: ['Wallet (Mitra)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nominal'],
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', format: 'float', example: 500000.00),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Penarikan mingguan'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Withdrawal requested successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/WithdrawalResource'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Bad Request / Insufficient available balance'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function withdraw(StoreWithdrawalRequest $request, EscrowService $escrowService): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $withdrawal = $escrowService->requestWithdrawal(
                $mitra,
                (float) $validated['nominal'],
                $validated['catatan'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan penarikan dana berhasil dibuat dan menunggu persetujuan.',
                'data' => new WithdrawalResource($withdrawal->load('mitra')),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'error_code' => 'ERR_INSUFFICIENT_AVAILABLE_BALANCE',
            ], 400);
        }
    }

    #[OA\Get(
        path: '/api/v1/mitra/withdrawals',
        summary: 'List partner withdrawal requests history',
        tags: ['Wallet (Mitra)'],
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
    public function withdrawals(Request $request): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $query = $mitra->withdrawals()->with('mitra')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $withdrawals = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat penarikan dana berhasil dimuat.',
            'data' => WithdrawalResource::collection($withdrawals)->response()->getData(true),
        ]);
    }
}
