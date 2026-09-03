<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Pesanan;
use App\Models\Refund;
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
        $pesanan = Pesanan::where('invoice', $invoice)->with('pembayaran')->firstOrFail();

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

        $refund = Refund::create([
            'pesanan_id' => $pesanan->id,
            'pembayaran_id' => $pesanan->pembayaran->id,
            'nominal' => $pesanan->total_bayar,
            'tipe' => 'manual',
            'alasan' => $validated['alasan'],
            'bank_tujuan' => $validated['bank_tujuan'],
            'rekening_tujuan' => $validated['rekening_tujuan'],
            'nama_tujuan' => $validated['nama_tujuan'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan refund berhasil dikirim dan akan diverifikasi oleh Admin.',
            'data' => new RefundResource($refund->load('pesanan')),
        ], 201);
    }
}
