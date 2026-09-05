<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\MitraRejectRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Mitra;
use App\Models\Refund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RefundController extends Controller
{
    /**
     * Get the authenticated mitra model.
     */
    private function getMitra(Request $request): Mitra
    {
        return Mitra::where('user_id', $request->user()->id)->firstOrFail();
    }

    #[OA\Get(
        path: '/api/v1/mitra/refunds',
        summary: 'List all refund requests for authenticated partner (Mitra Tier-1)',
        tags: ['Mitra Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partner refunds fetched successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $mitra = $this->getMitra($request);

        $query = Refund::where('mitra_id', $mitra->id)
            ->with(['pesanan.user', 'pesanan.basecamp', 'pembayaran'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $refunds = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengajuan refund mitra berhasil dimuat.',
            'data' => RefundResource::collection($refunds)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/refunds/{id}',
        summary: 'Get details of a specific refund request (Mitra Tier-1)',
        tags: ['Mitra Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Refund details fetched successfully'),
            new OA\Response(response: 404, description: 'Refund not found'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $mitra = $this->getMitra($request);

        $refund = Refund::where('mitra_id', $mitra->id)
            ->with(['pesanan.user', 'pesanan.basecamp', 'pembayaran'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail refund berhasil dimuat.',
            'data' => new RefundResource($refund),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/refunds/{id}/approve',
        summary: 'Approve refund request by partner (Mitra Tier-1)',
        tags: ['Mitra Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Refund approved by partner'),
            new OA\Response(response: 422, description: 'Refund cannot be approved in current status'),
        ]
    )]
    public function approve(Request $request, int $id): JsonResponse
    {
        $mitra = $this->getMitra($request);

        $refund = Refund::where('mitra_id', $mitra->id)
            ->with(['pesanan.basecamp'])
            ->findOrFail($id);

        if (! in_array($refund->status, ['pending', 'disputed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan refund ini sudah diproses sebelumnya.',
            ], 422);
        }

        DB::transaction(function () use ($refund, $mitra) {
            $pesanan = $refund->pesanan;

            $refund->update([
                'status' => 'approved_by_mitra',
                'nominal_disetujui' => $refund->nominal,
                'mitra_reviewed_at' => now(),
                'refunded_at' => now(),
            ]);

            $pesanan->update([
                'status' => 'refunded',
                'status_escrow' => 'refunded',
            ]);

            // Deduct from Mitra's pending escrow atomically
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
                    'catatan' => 'Pemotongan escrow karena refund disetujui Mitra untuk pesanan: '.$pesanan->invoice,
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan refund berhasil disetujui dan saldo escrow telah disesuaikan.',
            'data' => new RefundResource($refund->fresh(['pesanan', 'mitra'])),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/refunds/{id}/reject',
        summary: 'Reject refund request by partner with explanation (Mitra Tier-1)',
        tags: ['Mitra Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan_penolakan'],
                properties: [
                    new OA\Property(property: 'alasan_penolakan', type: 'string', example: 'Pembatalan melewati batas H-1 dan logistik telah disiapkan'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund rejected by partner'),
            new OA\Response(response: 422, description: 'Refund cannot be rejected in current status'),
        ]
    )]
    public function reject(MitraRejectRefundRequest $request, int $id): JsonResponse
    {
        $mitra = $this->getMitra($request);

        $refund = Refund::where('mitra_id', $mitra->id)->findOrFail($id);

        if ($refund->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan refund dengan status pending yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validated();

        $refund->update([
            'status' => 'rejected_by_mitra',
            'mitra_alasan_penolakan' => $validated['alasan_penolakan'],
            'mitra_reviewed_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan refund telah ditolak oleh Mitra. Pendaki dapat mengajukan banding jika diperlukan.',
            'data' => new RefundResource($refund->fresh(['pesanan', 'mitra'])),
        ]);
    }
}
