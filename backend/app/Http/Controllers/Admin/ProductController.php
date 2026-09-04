<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProdukResource;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/products',
        summary: 'List all products across all basecamps (Admin)',
        tags: ['Product (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'mitra_id', in: 'query', description: 'Filter products by mitra ID', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'basecamp_id', in: 'query', description: 'Filter products by basecamp ID', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'kategori', in: 'query', description: 'Filter products by category', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search products by name, description, basecamp, or partner', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Filter by active status', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Number of items per page (max 100)', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number for pagination', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'All products fetched successfully.'),
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
        $query = Produk::query()->with(['basecamp.mitra', 'basecamp.jalur.gunung', 'opentrip', 'tiket.kuotas']);

        if ($request->filled('basecamp_id')) {
            $query->where('basecamp_id', $request->query('basecamp_id'));
        }

        if ($request->filled('mitra_id')) {
            $query->whereHas('basecamp', function ($q) use ($request) {
                $q->where('mitra_id', $request->query('mitra_id'));
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->has('is_active') && $request->query('is_active') !== null && $request->query('is_active') !== '') {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhereHas('basecamp', function ($bq) use ($search) {
                        $bq->where('nama_basecamp', 'like', "%{$search}%")
                            ->orWhereHas('mitra', function ($mq) use ($search) {
                                $mq->where('nama_pemilik', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $products = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'All products fetched successfully.',
            'data' => ProdukResource::collection($products)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/products/{id}',
        summary: 'Get details of any specific product (Admin)',
        tags: ['Product (Admin)'],
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
        $produk = Produk::with(['basecamp.mitra', 'basecamp.jalur.gunung', 'opentrip', 'tiket.kuotas'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product details fetched successfully.',
            'data' => new ProdukResource($produk),
        ]);
    }
}
