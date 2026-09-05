<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mitra\UpdateMitraBasecampRequest;
use App\Http\Resources\BasecampResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BasecampController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/basecamps',
        summary: 'List all basecamps operated by authenticated partner (Mitra)',
        tags: ['Basecamp (Mitra)'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamps list retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar basecamp berhasil dimuat.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BasecampResource')),
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
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $basecamps = $mitra->basecamps()
            ->with(['jalur.gunung'])
            ->withCount(['staff', 'produks'])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar basecamp berhasil dimuat.',
            'data' => BasecampResource::collection($basecamps),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/mitra/basecamps/{id}',
        summary: 'Get details of a specific basecamp operated by this partner (Mitra)',
        tags: ['Basecamp (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Basecamp ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamp details retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Detail basecamp berhasil dimuat.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BasecampResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Basecamp not found'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $basecamp = $mitra->basecamps()
            ->with(['jalur.gunung', 'staff', 'produks'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail basecamp berhasil dimuat.',
            'data' => new BasecampResource($basecamp),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/mitra/basecamps/{id}',
        summary: 'Update operational details of a basecamp (Mitra)',
        tags: ['Basecamp (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Basecamp ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_basecamp'],
                properties: [
                    new OA\Property(property: 'nama_basecamp', type: 'string', example: 'Basecamp Selo Merbabu Utama'),
                    new OA\Property(property: 'latitude', type: 'string', example: '-7.452312'),
                    new OA\Property(property: 'longitude', type: 'string', example: '110.423123'),
                    new OA\Property(property: 'jam_operasional', type: 'string', example: '24 Jam Setiap Hari'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamp updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data basecamp berhasil diperbarui.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BasecampResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Basecamp not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateMitraBasecampRequest $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $basecamp = $mitra->basecamps()->findOrFail($id);

        $basecamp->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data basecamp berhasil diperbarui.',
            'data' => new BasecampResource($basecamp->fresh(['jalur.gunung', 'staff', 'produks'])),
        ]);
    }
}
