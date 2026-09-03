<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jalur\StoreJalurRequest;
use App\Http\Requests\Jalur\UpdateJalurRequest;
use App\Http\Requests\Jalur\UpdateStatusJalurRequest;
use App\Http\Resources\JalurPendakianResource;
use App\Models\JalurPendakian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class JalurController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/trails',
        summary: 'List all trails with filtering and search (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'gunung_id', in: 'query', description: 'Filter by mountain ID', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by trail status', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'close'])),
            new OA\Parameter(name: 'tingkat_kesulitan', in: 'query', description: 'Filter by difficulty level', required: false, schema: new OA\Schema(type: 'string', enum: ['mudah', 'sedang', 'sulit', 'ekstrem'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search trail by name', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number for pagination', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trails fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trails fetched successfully.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/JalurPendakianResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = JalurPendakian::query()->with(['gunung'])->withCount('basecamps');

        if ($request->filled('gunung_id')) {
            $query->where('gunung_id', $request->query('gunung_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('tingkat_kesulitan')) {
            $query->where('tingkat_kesulitan', $request->query('tingkat_kesulitan'));
        }

        if ($request->filled('search')) {
            $query->where('nama_jalur', 'like', '%'.$request->query('search').'%');
        }

        $perPage = (int) $request->query('per_page', 15);
        $trails = $query->paginate($perPage > 0 ? $perPage : 15);

        return response()->json([
            'status' => 'success',
            'message' => 'Trails fetched successfully.',
            'data' => JalurPendakianResource::collection($trails)->response()->getData(true),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/trails/{id}',
        summary: 'Get details of a specific trail (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Trail ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trail details fetched successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trail details fetched successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/JalurPendakianResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Trail not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $jalur = JalurPendakian::with(['gunung', 'basecamps.mitra.user'])->withCount('basecamps')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Trail details fetched successfully.',
            'data' => new JalurPendakianResource($jalur),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/trails',
        summary: 'Create a new trail for a mountain (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['gunung_id', 'nama_jalur', 'deskripsi', 'titik_awal_mdpl', 'titik_akhir_mdpl', 'waktu_tempuh', 'status', 'panjang_jalur', 'tingkat_kesulitan'],
                properties: [
                    new OA\Property(property: 'gunung_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nama_jalur', type: 'string', example: 'Jalur Cibodas'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Jalur pendakian yang berbatu dan terdapat air panas.'),
                    new OA\Property(property: 'titik_awal_mdpl', type: 'string', example: '1300 MDPL'),
                    new OA\Property(property: 'titik_akhir_mdpl', type: 'string', example: '2958 MDPL'),
                    new OA\Property(property: 'waktu_tempuh', type: 'string', example: '7 Jam'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'close'], example: 'open'),
                    new OA\Property(property: 'panjang_jalur', type: 'string', example: '9.7 Km'),
                    new OA\Property(property: 'tingkat_kesulitan', type: 'string', enum: ['mudah', 'sedang', 'sulit', 'ekstrem'], example: 'sedang'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Trail created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trail created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/JalurPendakianResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreJalurRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $jalur = JalurPendakian::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Trail created successfully.',
            'data' => new JalurPendakianResource($jalur->load(['gunung'])->loadCount('basecamps')),
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/trails/{id}',
        summary: 'Update an existing trail (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Trail ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['gunung_id', 'nama_jalur', 'deskripsi', 'titik_awal_mdpl', 'titik_akhir_mdpl', 'waktu_tempuh', 'status', 'panjang_jalur', 'tingkat_kesulitan'],
                properties: [
                    new OA\Property(property: 'gunung_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nama_jalur', type: 'string', example: 'Jalur Cibodas Baru'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Jalur dengan fasilitas lengkap.'),
                    new OA\Property(property: 'titik_awal_mdpl', type: 'string', example: '1300 MDPL'),
                    new OA\Property(property: 'titik_akhir_mdpl', type: 'string', example: '2958 MDPL'),
                    new OA\Property(property: 'waktu_tempuh', type: 'string', example: '6 Jam'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'close'], example: 'open'),
                    new OA\Property(property: 'panjang_jalur', type: 'string', example: '9.7 Km'),
                    new OA\Property(property: 'tingkat_kesulitan', type: 'string', enum: ['mudah', 'sedang', 'sulit', 'ekstrem'], example: 'sedang'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trail updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trail updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/JalurPendakianResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Trail not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateJalurRequest $request, int $id): JsonResponse
    {
        $jalur = JalurPendakian::findOrFail($id);
        $validated = $request->validated();

        $jalur->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Trail updated successfully.',
            'data' => new JalurPendakianResource($jalur->fresh(['gunung'])->loadCount('basecamps')),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/trails/{id}/status',
        summary: 'Quickly update open/close status of a trail (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Trail ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'close'], example: 'close'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trail status updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trail status updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/JalurPendakianResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Trail not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateStatus(UpdateStatusJalurRequest $request, int $id): JsonResponse
    {
        $jalur = JalurPendakian::findOrFail($id);
        $jalur->update(['status' => $request->validated()['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Trail status updated successfully.',
            'data' => new JalurPendakianResource($jalur->fresh(['gunung'])->loadCount('basecamps')),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/trails/{id}',
        summary: 'Delete a trail (Admin)',
        tags: ['Trail (Admin)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Trail ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trail deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Trail deleted successfully.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Cannot delete trail with linked active basecamps'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Trail not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $jalur = JalurPendakian::findOrFail($id);

        if ($jalur->basecamps()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus jalur pendakian yang memiliki relasi basecamp aktif.',
            ], 400);
        }

        $jalur->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Trail deleted successfully.',
            'data' => null,
        ]);
    }
}
