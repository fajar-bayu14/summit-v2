<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logbook\StoreLogbookRequest;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\LogbookResource;
use App\Models\Logbook;
use App\Models\Pesanan;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class LogbookController extends Controller
{
    #[OA\Post(
        path: '/api/v1/orders/{invoice}/logbook',
        summary: 'Upload summit proof and submit digital logbook',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['foto_summit'],
                    properties: [
                        new OA\Property(property: 'foto_summit', type: 'string', format: 'binary'),
                        new OA\Property(property: 'catatan_pendaki', type: 'string', nullable: true),
                        new OA\Property(property: 'latitude', type: 'string', nullable: true),
                        new OA\Property(property: 'longitude', type: 'string', nullable: true),
                        new OA\Property(property: 'waktu_summit', type: 'string', format: 'date-time', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Summit proof uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/LogbookResource'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Order not eligible / not on going / already submitted'),
            new OA\Response(response: 403, description: 'Unauthorized access'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreLogbookRequest $request, string $invoice): JsonResponse
    {
        $pesanan = Pesanan::where('invoice', $invoice)->with(['jalur.gunung'])->firstOrFail();

        if ($pesanan->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses terhadap pesanan ini.',
            ], 403);
        }

        if (! in_array($pesanan->status, ['paid', 'on_going', 'completed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bukti summit hanya dapat diunggah untuk pesanan yang aktif / sedang berjalan.',
                'error_code' => 'ERR_ORDER_NOT_ACTIVE',
            ], 400);
        }

        if ($pesanan->logbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bukti summit untuk pesanan ini sudah pernah diunggah.',
                'error_code' => 'ERR_LOGBOOK_ALREADY_EXISTS',
            ], 400);
        }

        $validated = $request->validated();
        $path = $request->file('foto_summit')->store('summit_proofs', 'public');

        $logbook = Logbook::create([
            'pesanan_id' => $pesanan->id,
            'user_id' => $request->user()->id,
            'foto_summit' => $path,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'waktu_summit' => $validated['waktu_summit'] ?? now(),
            'catatan_pendaki' => $validated['catatan_pendaki'] ?? null,
            'status_validasi' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Bukti summit berhasil diunggah dan menunggu validasi petugas pos check-out.',
            'data' => new LogbookResource($logbook->load(['pesanan.jalur.gunung', 'user'])),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/orders/{invoice}/logbook',
        summary: 'Get digital logbook details by order invoice',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logbook retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/LogbookResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Logbook not found'),
        ]
    )]
    public function show(Request $request, string $invoice): JsonResponse
    {
        $pesanan = Pesanan::where('invoice', $invoice)->with(['logbook.pesanan.jalur.gunung', 'logbook.user', 'logbook.validator'])->firstOrFail();

        if (! $pesanan->logbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catatan logbook belum diunggah untuk pesanan ini.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data logbook berhasil dimuat.',
            'data' => new LogbookResource($pesanan->logbook),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/orders/{invoice}/certificate',
        summary: 'Download climbing E-Certificate PDF',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF stream binary'),
            new OA\Response(response: 403, description: 'Logbook not approved yet'),
            new OA\Response(response: 404, description: 'Certificate not found'),
        ]
    )]
    public function certificate(Request $request, string $invoice, CertificateService $certificateService): Response
    {
        $pesanan = Pesanan::where('invoice', $invoice)->with(['logbook.pesanan.jalur.gunung', 'logbook.user'])->firstOrFail();

        if (! $pesanan->logbook || $pesanan->logbook->status_validasi !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Sertifikat digital belum tersedia karena bukti summit belum disetujui.',
            ], 403);
        }

        return $certificateService->streamCertificate($pesanan->logbook);
    }

    #[OA\Get(
        path: '/api/v1/pendaki/badges',
        summary: 'Get list of earned climbing badges (Pendaki)',
        tags: ['Logbook & Summit Proof'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Badges list retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BadgeResource')),
                    ]
                )
            ),
        ]
    )]
    public function badges(Request $request): JsonResponse
    {
        $logbooks = Logbook::where('user_id', $request->user()->id)
            ->where('status_validasi', 'approved')
            ->with(['pesanan.jalur.gunung', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar badge prestasi pendakian berhasil dimuat.',
            'data' => BadgeResource::collection($logbooks),
        ]);
    }
}
