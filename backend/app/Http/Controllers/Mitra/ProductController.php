<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\KuotaHarianTiketResource;
use App\Http\Resources\ProdukResource;
use App\Models\KuotaHarianTiket;
use App\Models\Produk;
use App\Models\ProdukOpentrip;
use App\Models\ProdukTiket;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/products',
        summary: 'List all products owned by this partner (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number for pagination', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Products fetched successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProdukResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Partner profile not found.',
            ], 403);
        }

        $basecampIds = $mitra->basecamps->pluck('id');
        $products = Produk::whereIn('basecamp_id', $basecampIds)
            ->with(['basecamp', 'opentrip', 'tiket.kuotas'])
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Products fetched successfully.',
            'data' => ProdukResource::collection($products)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/products',
        summary: 'Create a new product with opentrip/ticket details (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basecamp_id', 'nama_produk', 'kategori', 'harga'],
                properties: [
                    new OA\Property(property: 'basecamp_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nama_produk', type: 'string', example: 'Sewa Tenda Dome'),
                    new OA\Property(property: 'kategori', type: 'string', enum: ['ticket', 'rental', 'opentrip', 'guide', 'porter', 'transport', 'parkir', 'merchandise', 'kuliner'], example: 'rental'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Tenda double layer kapasitas 4 orang'),
                    new OA\Property(property: 'harga', type: 'number', format: 'float', example: 35000.00),
                    new OA\Property(property: 'stok', type: 'integer', example: 10),
                    new OA\Property(property: 'satuan', type: 'string', example: 'hari'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    // Parameter Kondisional Open Trip
                    new OA\Property(property: 'tanggal_berangkat', type: 'string', format: 'date', example: '2026-08-01'),
                    new OA\Property(property: 'tanggal_pulang', type: 'string', format: 'date', example: '2026-08-03'),
                    new OA\Property(property: 'meeting_point', type: 'string', example: 'Basecamp Selo'),
                    new OA\Property(property: 'minimal_peserta', type: 'integer', example: 5),
                    new OA\Property(property: 'maksimal_peserta', type: 'integer', example: 15),
                    // Parameter Kondisional Tiket
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 2),
                    new OA\Property(property: 'jam_buka', type: 'string', example: '07:00'),
                    new OA\Property(property: 'jam_tutup', type: 'string', example: '17:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Product created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProdukResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $produk = DB::transaction(function () use ($validated) {
            // Create main product
            $produk = Produk::create($validated);

            // Handle specific category details
            if ($validated['kategori'] === 'opentrip') {
                ProdukOpentrip::create(array_merge($validated, [
                    'produk_id' => $produk->id,
                    'sisa_kursi' => $validated['maksimal_peserta'],
                ]));
            } elseif ($validated['kategori'] === 'ticket') {
                ProdukTiket::create(array_merge($validated, [
                    'produk_id' => $produk->id,
                ]));
            }

            return $produk;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully.',
            'data' => new ProdukResource($produk->load(['basecamp', 'opentrip', 'tiket.kuotas'])),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/mitra/products/{id}',
        summary: 'Get details of a specific product (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Product ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product details fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Product details fetched successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProdukResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $produk = Produk::with(['basecamp', 'opentrip', 'tiket.kuotas'])->findOrFail($id);

        Gate::authorize('view', $produk);

        return response()->json([
            'status' => 'success',
            'message' => 'Product details fetched successfully.',
            'data' => new ProdukResource($produk),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/mitra/products/{id}',
        summary: 'Update an existing product (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Product ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basecamp_id', 'nama_produk', 'kategori', 'harga'],
                properties: [
                    new OA\Property(property: 'basecamp_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nama_produk', type: 'string', example: 'Sewa Tenda Dome Baru'),
                    new OA\Property(property: 'kategori', type: 'string', enum: ['ticket', 'rental', 'opentrip', 'guide', 'porter', 'transport', 'parkir', 'merchandise', 'kuliner'], example: 'rental'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Tenda double layer kapasitas 4 orang teruji air hujan'),
                    new OA\Property(property: 'harga', type: 'number', format: 'float', example: 40000.00),
                    new OA\Property(property: 'stok', type: 'integer', example: 8),
                    new OA\Property(property: 'satuan', type: 'string', example: 'hari'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Product updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProdukResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $produk = Produk::findOrFail($id);

        Gate::authorize('update', $produk);

        $validated = $request->validated();

        $produk = DB::transaction(function () use ($produk, $validated) {
            // Update main product
            $produk->update($validated);

            // Clean details if category changed
            if ($produk->wasChanged('kategori')) {
                $produk->opentrip()->delete();
                $produk->tiket()->delete();
            }

            // Sync detail tables based on new/current category
            if ($validated['kategori'] === 'opentrip') {
                ProdukOpentrip::updateOrCreate(
                    ['produk_id' => $produk->id],
                    array_merge($validated, [
                        'sisa_kursi' => $validated['maksimal_peserta'] ?? ($produk->opentrip?->sisa_kursi ?? $validated['maksimal_peserta']),
                    ])
                );
            } elseif ($validated['kategori'] === 'ticket') {
                ProdukTiket::updateOrCreate(
                    ['produk_id' => $produk->id],
                    $validated
                );
            }

            return $produk;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully.',
            'data' => new ProdukResource($produk->load(['basecamp', 'opentrip', 'tiket.kuotas'])),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/mitra/products/{id}',
        summary: 'Delete a product (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Product ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Product deleted successfully.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $produk = Produk::findOrFail($id);

        Gate::authorize('delete', $produk);

        $produk->delete(); // Cascades on delete automatically handles specific tables

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully.',
            'data' => null,
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/mitra/products/{id}/toggle-status',
        summary: 'Toggle active status of a product (Mitra)',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_active'],
                properties: [
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product status updated'),
        ]
    )]
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $produk = Produk::findOrFail($id);
        Gate::authorize('update', $produk);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $produk->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status produk berhasil diperbarui.',
            'data' => new ProdukResource($produk->load(['basecamp', 'opentrip', 'tiket.kuotas'])),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/products/{id}/quotas',
        summary: 'List daily quotas for a ticket product',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Quotas list fetched'),
        ]
    )]
    public function quotas(Request $request, int $id): JsonResponse
    {
        $produk = Produk::with('tiket')->findOrFail($id);
        Gate::authorize('view', $produk);

        if (! $produk->tiket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk ini bukan merupakan produk tiket.',
            ], 422);
        }

        $query = KuotaHarianTiket::where('produk_tiket_id', $produk->tiket->id)->orderBy('tanggal');

        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->query('end_date'));
        }

        $quotas = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kuota harian berhasil dimuat.',
            'data' => KuotaHarianTiketResource::collection($quotas),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/products/{id}/quotas/batch',
        summary: 'Set batch daily quotas for a ticket product',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['start_date', 'end_date', 'kuota_total'],
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-09-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-09-30'),
                    new OA\Property(property: 'kuota_total', type: 'integer', example: 100),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Batch quota set successfully'),
        ]
    )]
    public function batchQuotas(Request $request, int $id): JsonResponse
    {
        $produk = Produk::with('tiket')->findOrFail($id);
        Gate::authorize('update', $produk);

        if (! $produk->tiket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk ini bukan merupakan produk tiket.',
            ], 422);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'kuota_total' => ['required', 'integer', 'min:1'],
        ]);

        $period = CarbonPeriod::create($validated['start_date'], $validated['end_date']);

        DB::transaction(function () use ($period, $produk, $validated) {
            foreach ($period as $date) {
                KuotaHarianTiket::updateOrCreate(
                    [
                        'produk_tiket_id' => $produk->tiket->id,
                        'tanggal' => $date->format('Y-m-d'),
                    ],
                    [
                        'kuota_total' => $validated['kuota_total'],
                        'kuota_tersisa' => $validated['kuota_total'],
                    ]
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Kuota harian berhasil diset untuk rentang tanggal tersebut.',
            'data' => null,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/mitra/quotas/{quota_id}',
        summary: 'Update quota for a specific date',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'quota_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kuota_total'],
                properties: [
                    new OA\Property(property: 'kuota_total', type: 'integer', example: 120),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Quota updated successfully'),
        ]
    )]
    public function updateQuota(Request $request, int $quota_id): JsonResponse
    {
        $quota = KuotaHarianTiket::with('tiket.produk')->findOrFail($quota_id);
        $produk = $quota->tiket->produk;
        Gate::authorize('update', $produk);

        $validated = $request->validate([
            'kuota_total' => ['required', 'integer', 'min:1'],
        ]);

        $usedQuota = $quota->kuota_total - $quota->kuota_tersisa;
        if ($validated['kuota_total'] < $usedQuota) {
            return response()->json([
                'status' => 'error',
                'message' => 'Total kuota baru tidak boleh lebih kecil dari tiket yang sudah terpesan.',
            ], 422);
        }

        $quota->update([
            'kuota_total' => $validated['kuota_total'],
            'kuota_tersisa' => $validated['kuota_total'] - $usedQuota,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kuota harian berhasil diperbarui.',
            'data' => new KuotaHarianTiketResource($quota),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/mitra/products/{id}/stock',
        summary: 'Update stock for a rental product',
        tags: ['Product (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['stok'],
                properties: [
                    new OA\Property(property: 'stok', type: 'integer', example: 20),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Stock updated'),
        ]
    )]
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $produk = Produk::findOrFail($id);
        Gate::authorize('update', $produk);

        $validated = $request->validate([
            'stok' => ['required', 'integer', 'min:0'],
        ]);

        $produk->update(['stok' => $validated['stok']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stok produk berhasil diperbarui.',
            'data' => new ProdukResource($produk->load(['basecamp', 'opentrip', 'tiket.kuotas'])),
        ]);
    }
}
