<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\DisputeRefundRequest;
use App\Http\Requests\Refund\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Pesanan;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RefundController extends Controller
{
    #[OA\Post(
        path: '/api/v1/orders/{invoice}/refund-request',
        summary: 'Request a refund for a paid/eligible order (Pendaki)',
        tags: ['Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan', 'bank_tujuan', 'rekening_tujuan', 'nama_tujuan'],
                properties: [
                    new OA\Property(property: 'alasan', type: 'string', example: 'Penutupan jalur darurat cuaca ekstrem'),
                    new OA\Property(property: 'bank_tujuan', type: 'string', example: 'BCA'),
                    new OA\Property(property: 'rekening_tujuan', type: 'string', example: '1234567890'),
                    new OA\Property(property: 'nama_tujuan', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'nominal', type: 'number', nullable: true, example: 50000),
                    new OA\Property(property: 'refund_category', type: 'string', enum: ['pre_trip', 'incident', 'force_majeure', 'dispute'], example: 'pre_trip'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Refund requested successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/RefundResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Order not eligible for refund'),
        ]
    )]
    public function store(StoreRefundRequest $request, string $invoice): JsonResponse
    {
        $pesanan = Pesanan::where('invoice', $invoice)->with(['pembayaran', 'basecamp.mitra'])->firstOrFail();

        // Check ownership
        if ($pesanan->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses terhadap pesanan ini.',
            ], 403);
        }

        if (! in_array($pesanan->status, ['paid', 'on_going', 'cancelled'], true) || ! $pesanan->pembayaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan ini tidak memenuhi syarat untuk pengajuan refund.',
            ], 422);
        }

        $validated = $request->validated();

        // Calculate refund amount based on date difference
        $today = Carbon::now()->startOfDay();
        $tripDate = Carbon::parse($pesanan->tanggal_booking)->startOfDay();
        $selisihHari = $today->diffInDays($tripDate, false);

        $category = $validated['refund_category'] ?? 'pre_trip';
        $nominal = (float) $pesanan->total_bayar;

        if ($pesanan->status === 'paid' && $category === 'pre_trip') {
            if ($selisihHari >= 3) {
                // H-3 or more: 100% full refund
                $nominal = (float) $pesanan->total_bayar;
            } elseif ($selisihHari >= 1) {
                // H-1 to H-2: 50% partial refund
                $nominal = round((float) $pesanan->total_bayar * 0.50, 2);
            } else {
                // Hari H: custom incident amount or default total
                $category = 'incident';
                if (! empty($validated['nominal'])) {
                    $nominal = min((float) $validated['nominal'], (float) $pesanan->total_bayar);
                }
            }
        } elseif (! empty($validated['nominal'])) {
            $nominal = min((float) $validated['nominal'], (float) $pesanan->total_bayar);
        }

        $refund = Refund::create([
            'pesanan_id' => $pesanan->id,
            'pembayaran_id' => $pesanan->pembayaran->id,
            'mitra_id' => $pesanan->basecamp?->mitra_id,
            'refund_category' => $category,
            'nominal' => $nominal,
            'tipe' => 'manual',
            'alasan' => $validated['alasan'],
            'bank_tujuan' => $validated['bank_tujuan'],
            'rekening_tujuan' => $validated['rekening_tujuan'],
            'nama_tujuan' => $validated['nama_tujuan'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan refund berhasil dikirim dan akan diverifikasi oleh Mitra Pengelola.',
            'data' => new RefundResource($refund->load('pesanan')),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/refunds/{id}/dispute',
        summary: 'Escalate a rejected refund to Admin Dispute Center (Pendaki)',
        tags: ['Refund'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan_dispute'],
                properties: [
                    new OA\Property(property: 'alasan_dispute', type: 'string', example: 'Mitra menolak sepihak padahal tenda memang bocor sejak pos awal'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund escalated to dispute center'),
            new OA\Response(response: 422, description: 'Refund cannot be disputed in current status'),
        ]
    )]
    public function dispute(DisputeRefundRequest $request, int $id): JsonResponse
    {
        $refund = Refund::with('pesanan')->findOrFail($id);

        if ($refund->pesanan->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses terhadap pengajuan refund ini.',
            ], 403);
        }

        if ($refund->status !== 'rejected_by_mitra') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pengajuan refund yang ditolak oleh Mitra yang dapat dieskalasi ke Pusat Sengketa Admin.',
            ], 422);
        }

        $validated = $request->validated();

        $refund->update([
            'status' => 'disputed',
            'is_disputed' => true,
            'disputed_at' => now(),
            'dispute_reason' => $validated['alasan_dispute'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan banding sengketa berhasil diteruskan ke Admin Platform untuk peninjauan.',
            'data' => new RefundResource($refund->fresh('pesanan')),
        ]);
    }
}
