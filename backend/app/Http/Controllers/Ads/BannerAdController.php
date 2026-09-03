<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerAdResource;
use App\Models\BannerAd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BannerAdController extends Controller
{
    #[OA\Get(
        path: '/api/v1/ads/banners',
        summary: 'Get active promotional banner ads (Public)',
        tags: ['Ads & Monetization'],
        parameters: [
            new OA\Parameter(name: 'posisi', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['home_top', 'mountain_detail', 'search_sidebar'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Active banners fetched',
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
        $today = now()->toDateString();

        $query = BannerAd::where('is_active', true)
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->with('mitra');

        if ($request->filled('posisi')) {
            $query->where('posisi', $request->query('posisi'));
        }

        $banners = $query->latest()->get();

        // Increment impression counters
        if ($banners->isNotEmpty()) {
            BannerAd::whereIn('id', $banners->pluck('id'))->increment('total_impressions');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Banner iklan aktif berhasil dimuat.',
            'data' => BannerAdResource::collection($banners),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/ads/banners/{id}/click',
        summary: 'Record banner ad click (Public)',
        tags: ['Ads & Monetization'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Click recorded'),
            new OA\Response(response: 404, description: 'Banner not found'),
        ]
    )]
    public function click(int $id): JsonResponse
    {
        $banner = BannerAd::findOrFail($id);
        $banner->increment('total_clicks');

        return response()->json([
            'status' => 'success',
            'message' => 'Klik banner berhasil dicatat.',
        ]);
    }
}
