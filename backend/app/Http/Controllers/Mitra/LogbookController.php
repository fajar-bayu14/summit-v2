<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logbook\VerifyLogbookRequest;
use App\Http\Resources\LogbookResource;
use App\Models\Logbook;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LogbookController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/logbooks',
        summary: 'List summit proof validation submissions for Mitra basecamp',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status_validasi', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logbooks list retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LogbookResource')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $basecampIds = $mitra ? $mitra->basecamps()->pluck('id') : [];

        $query = Logbook::whereHas('pesanan', function ($q) use ($basecampIds, $request) {
            if ($request->user()->role !== 'admin') {
                $q->whereIn('basecamp_id', $basecampIds);
            }
        })->with(['pesanan.jalur.gunung', 'user', 'validator'])->latest();

        if ($request->filled('status_validasi')) {
            $query->where('status_validasi', $request->query('status_validasi'));
        }

        $logbooks = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar permohonan validasi logbook berhasil dimuat.',
            'data' => LogbookResource::collection($logbooks)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/logbooks/{id}/verify',
        summary: 'Verify summit proof and approve/reject climbing completion',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status_validasi'],
                properties: [
                    new OA\Property(property: 'status_validasi', type: 'string', enum: ['approved', 'rejected'], example: 'approved'),
                    new OA\Property(property: 'catatan_petugas', type: 'string', nullable: true, example: 'Foto summit sah dan terverifikasi di puncak'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logbook verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/LogbookResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Already verified / validation error'),
        ]
    )]
    public function verify(
        VerifyLogbookRequest $request,
        int $id,
        EscrowService $escrowService
    ): JsonResponse {
        $logbook = Logbook::with(['pesanan.basecamp.mitra', 'pesanan.jalur.gunung', 'user'])->findOrFail($id);

        if ($logbook->status_validasi !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Logbook ini sudah diverifikasi sebelumnya.',
            ], 422);
        }

        $validated = $request->validated();
        $isApproved = $validated['status_validasi'] === 'approved';

        DB::transaction(function () use ($logbook, $validated, $isApproved, $request, $escrowService) {
            $logbook->update([
                'status_validasi' => $validated['status_validasi'],
                'catatan_petugas' => $validated['catatan_petugas'] ?? null,
                'validated_at' => now(),
                'validated_by' => $request->user()->id,
            ]);

            if ($isApproved) {
                $pesanan = $logbook->pesanan;
                $pesanan->update([
                    'status' => 'completed',
                    'status_escrow' => 'released',
                ]);

                // Move holding escrow funds to available balance
                $escrowService->releaseEscrowToAvailable($pesanan);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => $isApproved
                ? 'Bukti summit berhasil disetujui, pesanan selesai, dan sertifikat digital telah diterbitkan.'
                : 'Bukti summit ditolak oleh petugas.',
            'data' => new LogbookResource($logbook->fresh(['pesanan.jalur.gunung', 'user', 'validator'])),
        ]);
    }
}
