<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Basecamp\StoreBasecampRequest;
use App\Http\Requests\Basecamp\UpdateBasecampRequest;
use App\Http\Resources\BasecampResource;
use App\Models\Basecamp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BasecampController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/basecamps',
        summary: 'List all basecamps (Admin)',
        tags: ['Basecamp (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number for pagination', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamps fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamps fetched successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BasecampResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Basecamp::with(['mitra.user', 'jalur.gunung']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_basecamp', 'like', "%{$search}%")
                    ->orWhereHas('mitra', function ($mq) use ($search) {
                        $mq->where('nama_pemilik', 'like', "%{$search}%");
                    })
                    ->orWhereHas('jalur', function ($jq) use ($search) {
                        $jq->where('nama_jalur', 'like', "%{$search}%")
                            ->orWhereHas('gunung', function ($gq) use ($search) {
                                $gq->where('nama_gunung', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('mitra_id')) {
            $query->where('mitra_id', $request->query('mitra_id'));
        }

        if ($request->filled('jalur_id')) {
            $query->where('jalur_id', $request->query('jalur_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $basecamps = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamps fetched successfully.',
            'data' => BasecampResource::collection($basecamps)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/basecamps',
        summary: 'Create a new basecamp (Admin)',
        tags: ['Basecamp (Admin)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mitra_id', 'jalur_id', 'nama_basecamp', 'latitude', 'longitude', 'jam_operasional'],
                properties: [
                    new OA\Property(property: 'mitra_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 2),
                    new OA\Property(property: 'nama_basecamp', type: 'string', example: 'Basecamp Merbabu via Selo'),
                    new OA\Property(property: 'latitude', type: 'string', example: '-7.441234'),
                    new OA\Property(property: 'longitude', type: 'string', example: '110.421234'),
                    new OA\Property(property: 'jam_operasional', type: 'string', example: '24 Jam'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Basecamp created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamp created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BasecampResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBasecampRequest $request): JsonResponse
    {
        $basecamp = Basecamp::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamp created successfully.',
            'data' => new BasecampResource($basecamp->load(['mitra.user', 'jalur'])),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/basecamps/{id}',
        summary: 'Get details of a specific basecamp (Admin)',
        tags: ['Basecamp (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Basecamp ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamp details fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamp details fetched successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BasecampResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Basecamp not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $basecamp = Basecamp::with(['mitra.user', 'jalur.gunung', 'produks.opentrip', 'produks.tiket.kuotas'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamp details fetched successfully.',
            'data' => new BasecampResource($basecamp),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/basecamps/{id}',
        summary: 'Update an existing basecamp (Admin)',
        tags: ['Basecamp (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Basecamp ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['mitra_id', 'jalur_id', 'nama_basecamp', 'latitude', 'longitude', 'jam_operasional'],
                properties: [
                    new OA\Property(property: 'mitra_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jalur_id', type: 'integer', example: 2),
                    new OA\Property(property: 'nama_basecamp', type: 'string', example: 'Basecamp Merbabu via Selo Updated'),
                    new OA\Property(property: 'latitude', type: 'string', example: '-7.441235'),
                    new OA\Property(property: 'longitude', type: 'string', example: '110.421235'),
                    new OA\Property(property: 'jam_operasional', type: 'string', example: '07:00 - 22:00'),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamp updated successfully.'),
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
    public function update(UpdateBasecampRequest $request, int $id): JsonResponse
    {
        $basecamp = Basecamp::findOrFail($id);
        $basecamp->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamp updated successfully.',
            'data' => new BasecampResource($basecamp->load(['mitra.user', 'jalur'])),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/basecamps/{id}',
        summary: 'Delete a basecamp (Admin)',
        tags: ['Basecamp (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Basecamp ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Basecamp deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamp deleted successfully.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Basecamp not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $basecamp = Basecamp::findOrFail($id);
        $basecamp->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamp deleted successfully.',
            'data' => null,
        ]);
    }
}
