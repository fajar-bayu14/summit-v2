<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\PesananResource;
use App\Models\DetailPesanan;
use App\Models\Pesanan;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesananController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/orders',
        summary: 'List all incoming bookings for this partner (Mitra) and query revenue metrics',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by order status', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'paid', 'cancelled', 'refunded'])),
            new OA\Parameter(name: 'basecamp_id', in: 'query', description: 'Filter by specific basecamp ID owned by Mitra', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_booking', in: 'query', description: 'Filter by booking date (Y-m-d)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by invoice number, user name or member name', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number for pagination', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Orders and revenue metrics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Mitra orders fetched successfully.'),
                        new OA\Property(property: 'meta', type: 'object', properties: [
                            new OA\Property(property: 'total_pendapatan_bersih', type: 'number', format: 'float', example: 250000.00),
                            new OA\Property(property: 'total_transaksi_paid', type: 'integer', example: 5),
                        ]),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PesananResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - only for role mitra'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil Mitra tidak ditemukan.',
            ], 403);
        }

        $basecampIds = $mitra->basecamps->pluck('id');

        $query = Pesanan::whereIn('basecamp_id', $basecampIds)
            ->with(['user.pendaki', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('basecamp_id')) {
            // Ensure basecamp_id belongs to the Mitra
            if ($basecampIds->contains($request->query('basecamp_id'))) {
                $query->where('basecamp_id', $request->query('basecamp_id'));
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk basecamp ini.',
                ], 403);
            }
        }

        if ($request->filled('tanggal_booking')) {
            $query->whereDate('tanggal_booking', $request->query('tanggal_booking'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('anggotas', function ($aq) use ($search) {
                        $aq->where('nama_anggota', 'like', "%{$search}%");
                    });
            });
        }

        // Calculate Revenue Metrics for PAID transactions
        // Note: We use a separate query on the filtered basecamp set to sum up paid revenue metrics
        $paidRevenueStats = Pesanan::whereIn('basecamp_id', $basecampIds)
            ->where('status', 'paid')
            ->selectRaw('SUM(pendapatan_mitra) as total_revenue, COUNT(id) as paid_count')
            ->first();

        // Paginate results
        $pesanans = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Mitra orders fetched successfully.',
            'meta' => [
                'total_pendapatan_bersih' => (float) ($paidRevenueStats->total_revenue ?? 0.00),
                'total_transaksi_paid' => (int) ($paidRevenueStats->paid_count ?? 0),
            ],
            'data' => PesananResource::collection($pesanans)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/orders/{id}',
        summary: 'Get details of a specific incoming booking (Mitra)',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Order (Pesanan) ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order details fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Order details fetched successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - only for role mitra or owned basecamp'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $pesanan = Pesanan::with(['user.pendaki', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])
            ->findOrFail($id);

        Gate::authorize('view', $pesanan);

        return response()->json([
            'status' => 'success',
            'message' => 'Order details fetched successfully.',
            'data' => new PesananResource($pesanan),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/mitra/orders/{pesananId}/items/{itemId}',
        summary: 'Update the operational status of an item in a booking (Mitra)',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'pesananId', in: 'path', description: 'Order ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId', in: 'path', description: 'Detail Pesanan (Item) ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status_operasional'],
                properties: [
                    new OA\Property(property: 'status_operasional', type: 'string', enum: ['pending', 'ready', 'active', 'completed', 'cancelled'], example: 'ready'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Operational status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Item operational status updated successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order or Item not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateItemStatus(Request $request, int $pesananId, int $itemId): JsonResponse
    {
        $pesanan = Pesanan::findOrFail($pesananId);

        Gate::authorize('update', $pesanan);

        $detail = DetailPesanan::where('pesanan_id', $pesanan->id)
            ->where('id', $itemId)
            ->firstOrFail();

        $request->validate([
            'status_operasional' => ['required', 'string', 'in:pending,ready,active,completed,cancelled'],
        ], [
            'status_operasional.in' => 'Status operasional tidak valid.',
        ]);

        $detail->update([
            'status_operasional' => $request->input('status_operasional'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item operational status updated successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/orders/{id}/check-in',
        summary: 'Check in a climber booking order at the basecamp by ID (Mitra)',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Order (Pesanan) ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Climber check-in completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-in berhasil. Rombongan pendaki telah tercatat aktif mendaki.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Order not eligible for check-in'),
        ]
    )]
    public function checkIn(int $id): JsonResponse
    {
        $pesanan = Pesanan::with(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])
            ->findOrFail($id);

        Gate::authorize('update', $pesanan);

        return $this->processCheckIn($pesanan);
    }

    #[OA\Post(
        path: '/api/v1/mitra/orders/check-in',
        summary: 'Check in a climber booking order by invoice number or QR code scan (Mitra)',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['invoice'],
                properties: [
                    new OA\Property(property: 'invoice', type: 'string', example: 'INV/A/PAID'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Climber check-in completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-in berhasil. Rombongan pendaki telah tercatat aktif mendaki.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Invoice not found'),
            new OA\Response(response: 422, description: 'Validation error or not eligible for check-in'),
        ]
    )]
    public function checkInByCode(Request $request): JsonResponse
    {
        $request->validate([
            'invoice' => ['required', 'string'],
        ], [
            'invoice.required' => 'Nomor invoice atau kode QR wajib diisi.',
        ]);

        $pesanan = Pesanan::with(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])
            ->where('invoice', $request->input('invoice'))
            ->firstOrFail();

        Gate::authorize('update', $pesanan);

        return $this->processCheckIn($pesanan);
    }

    /**
     * Common logic to execute climber check-in transaction.
     */
    private function processCheckIn(Pesanan $pesanan): JsonResponse
    {
        if ($pesanan->status === 'on_going') {
            return response()->json([
                'status' => 'error',
                'message' => 'Rombongan pendaki ini sudah melakukan check-in sebelumnya.',
            ], 422);
        }

        if ($pesanan->status !== 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pesanan berstatus paid (sudah lunas) yang dapat melakukan check-in.',
            ], 422);
        }

        $pesanan->update([
            'status' => 'on_going',
        ]);

        // Activate items that were pending or ready
        $pesanan->details()
            ->whereIn('status_operasional', ['pending', 'ready'])
            ->update(['status_operasional' => 'active']);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil. Rombongan pendaki telah tercatat aktif mendaki.',
            'data' => new PesananResource($pesanan->fresh(['user.pendaki', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/orders/{id}/check-out',
        summary: 'Check out and complete a climber hiking order at basecamp (Mitra)',
        description: 'Completes active hiking order, sets operational items to completed, and releases escrow balance to partner available balance',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Order (Pesanan) ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order checked out and completed successfully, escrow released',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Check-out berhasil. Pendakian telah selesai dan dana escrow telah dicairkan ke saldo aktif mitra.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PesananResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Order not eligible for check-out'),
        ]
    )]
    public function checkOut(int $id, EscrowService $escrowService): JsonResponse
    {
        $pesanan = Pesanan::with(['user.pendaki', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])
            ->findOrFail($id);

        Gate::authorize('update', $pesanan);

        if ($pesanan->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan ini sudah diselesaikan sebelumnya.',
            ], 422);
        }

        if ($pesanan->status !== 'on_going') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pesanan berstatus on_going (sedang aktif mendaki) yang dapat di-check out / diselesaikan.',
            ], 422);
        }

        DB::transaction(function () use ($pesanan, $escrowService) {
            $pesanan->update([
                'status' => 'completed',
                'status_escrow' => 'released',
            ]);

            // Complete operational items (unless already cancelled)
            $pesanan->details()
                ->where('status_operasional', '!=', 'cancelled')
                ->update(['status_operasional' => 'completed']);

            // Release escrow funds to available balance
            $escrowService->releaseEscrowToAvailable($pesanan);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out berhasil. Pendakian telah selesai dan dana escrow telah dicairkan ke saldo aktif mitra.',
            'data' => new PesananResource($pesanan->fresh(['user.pendaki', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran'])),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/orders/{id}/ktp',
        summary: 'View or stream the KYC identity document/KTP of the climber for this order (Mitra)',
        tags: ['Pesanan (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Order ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Streamed KTP file response', content: new OA\MediaType(mediaType: 'image/*')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - only for basecamp owner mitra'),
            new OA\Response(response: 404, description: 'KTP document file not found'),
        ]
    )]
    public function viewKycDocument(int $id): StreamedResponse
    {
        $pesanan = Pesanan::with(['basecamp', 'user.pendaki'])->findOrFail($id);

        Gate::authorize('view', $pesanan);

        $pendaki = $pesanan->user?->pendaki;

        if (! $pendaki || ! $pendaki->foto_identitas || ! Storage::disk('local')->exists($pendaki->foto_identitas)) {
            abort(404, 'Dokumen KTP pendaki tidak ditemukan.');
        }

        return Storage::disk('local')->response($pendaki->foto_identitas);
    }
}
