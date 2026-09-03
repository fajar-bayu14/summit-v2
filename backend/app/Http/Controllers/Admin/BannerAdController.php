<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\StoreBannerAdRequest;
use App\Http\Requests\Ads\UpdateBannerAdRequest;
use App\Http\Resources\BannerAdResource;
use App\Models\BannerAd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class BannerAdController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/ads/banners',
        summary: 'List all promotional banner ads (Admin)',
        tags: ['Ads (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'posisi', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banners listed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BannerAdResource')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = BannerAd::with('mitra')->latest();

        if ($request->filled('posisi')) {
            $query->where('posisi', $request->query('posisi'));
        }

        $banners = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar banner promosi berhasil dimuat.',
            'data' => BannerAdResource::collection($banners)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/ads/banners',
        summary: 'Create a new promotional banner ad slot (Admin)',
        tags: ['Ads (Admin)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'gambar', 'posisi', 'tanggal_mulai', 'tanggal_selesai', 'is_active'],
                    properties: [
                        new OA\Property(property: 'mitra_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'judul', type: 'string'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary'),
                        new OA\Property(property: 'link_url', type: 'string', nullable: true),
                        new OA\Property(property: 'posisi', type: 'string', enum: ['home_top', 'mountain_detail', 'search_sidebar']),
                        new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date'),
                        new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date'),
                        new OA\Property(property: 'is_active', type: 'boolean'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Banner created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BannerAdResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBannerAdRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $gambarPath = $request->file('gambar')->store('banners', 'public');

        $banner = BannerAd::create([
            'mitra_id' => $validated['mitra_id'] ?? null,
            'judul' => $validated['judul'],
            'gambar' => $gambarPath,
            'link_url' => $validated['link_url'] ?? null,
            'posisi' => $validated['posisi'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'is_active' => (bool) $validated['is_active'],
            'total_impressions' => 0,
            'total_clicks' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Banner promosi berhasil ditambahkan.',
            'data' => new BannerAdResource($banner->load('mitra')),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/ads/banners/{id}',
        summary: 'Get banner ad details (Admin)',
        tags: ['Ads (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner details retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BannerAdResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $banner = BannerAd::with('mitra')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail banner promosi berhasil dimuat.',
            'data' => new BannerAdResource($banner),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/ads/banners/{id}',
        summary: 'Update promotional banner ad slot (Admin)',
        tags: ['Ads (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BannerAdResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function update(UpdateBannerAdRequest $request, int $id): JsonResponse
    {
        $banner = BannerAd::findOrFail($id);
        $validated = $request->validated();

        if ($request->hasFile('gambar')) {
            if ($banner->gambar && Storage::disk('public')->exists($banner->gambar)) {
                Storage::disk('public')->delete($banner->gambar);
            }
            $validated['gambar'] = $request->file('gambar')->store('banners', 'public');
        }

        $banner->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Banner promosi berhasil diperbarui.',
            'data' => new BannerAdResource($banner->fresh('mitra')),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/ads/banners/{id}',
        summary: 'Delete promotional banner ad slot (Admin)',
        tags: ['Ads (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Banner deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', example: null),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $banner = BannerAd::findOrFail($id);

        if ($banner->gambar && Storage::disk('public')->exists($banner->gambar)) {
            Storage::disk('public')->delete($banner->gambar);
        }

        $banner->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Banner promosi berhasil dihapus.',
            'data' => null,
        ]);
    }
}
