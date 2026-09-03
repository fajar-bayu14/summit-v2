<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\ProcessRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RefundController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/refunds',
        summary: 'List all refund requests (Admin)',
        tags: ['Refund (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'success', 'failed'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refund requests fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RefundResource')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Refund::with(['pesanan.user', 'pembayaran'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $refunds = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan refund berhasil dimuat.',
            'data' => RefundResource::collection($refunds)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/refunds/{id}/process',
        summary: 'Process and approve/reject a refund request (Admin)',
        tags: ['Refund (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['success', 'failed'], example: 'success'),
                    new OA\Property(property: 'tipe', type: 'string', enum: ['auto', 'manual'], example: 'manual'),
                    new OA\Property(property: 'bukti_transfer', type: 'string', nullable: true, example: 'proofs/refund_123.jpg'),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Dana telah ditransfer ke rekening pendaki'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refund processed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RefundResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error / already processed'),
        ]
    )]
    public function process(ProcessRefundRequest $request, int $id): JsonResponse
    {
        $refund = Refund::with('pesanan.basecamp.mitra')->findOrFail($id);

        if ($refund->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan refund ini sudah diproses sebelumnya.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($refund, $validated) {
            $isSuccess = $validated['status'] === 'success';

            $refund->update([
                'status' => $validated['status'],
                'tipe' => $validated['tipe'] ?? $refund->tipe,
                'bukti_transfer' => $validated['bukti_transfer'] ?? $refund->bukti_transfer,
                'refunded_at' => $isSuccess ? now() : null,
            ]);

            if ($isSuccess) {
                $pesanan = $refund->pesanan;
                $pesanan->update(['status' => 'refunded']);

                // Deduct from Mitra's pending escrow if holding
                $mitra = $pesanan->basecamp?->mitra;
                if ($mitra) {
                    $wallet = Wallet::where('mitra_id', $mitra->id)->lockForUpdate()->first();
                    if ($wallet) {
                        $amount = (float) $pesanan->pendapatan_mitra;
                        $newPending = max(0.00, (float) bcsub((string) $wallet->saldo_pending, (string) $amount, 2));
                        $wallet->saldo_pending = $newPending;
                        $wallet->save();

                        WalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'pesanan_id' => $pesanan->id,
                            'type' => 'refund_deduction',
                            'nominal' => $amount,
                            'saldo_pending_after' => $wallet->saldo_pending,
                            'saldo_available_after' => $wallet->saldo_available,
                            'catatan' => 'Pemotongan escrow akibat refund pesanan: '.$pesanan->invoice,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Proses refund berhasil diperbarui.',
            'data' => new RefundResource($refund->fresh('pesanan')),
        ]);
    }
}
