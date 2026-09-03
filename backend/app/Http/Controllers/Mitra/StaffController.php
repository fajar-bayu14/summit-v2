<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreMitraStaffRequest;
use App\Http\Requests\Staff\UpdateMitraStaffRequest;
use App\Http\Resources\MitraStaffResource;
use App\Models\MitraStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StaffController extends Controller
{
    #[OA\Get(
        path: '/api/v1/mitra/staff',
        summary: 'List all staff (guides/porters) for the authenticated partner',
        tags: ['Staff (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'query', description: 'Filter by staff role (guide, porter, petugas)', required: false, schema: new OA\Schema(type: 'string', enum: ['guide', 'porter', 'petugas'])),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff list retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar staf berhasil dimuat.'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MitraStaffResource')),
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
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $query = $mitra->staff()->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('basecamp_id')) {
            $query->where('basecamp_id', $request->query('basecamp_id'));
        }

        $staff = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar staf berhasil dimuat.',
            'data' => MitraStaffResource::collection($staff)->response()->getData(true),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/mitra/staff',
        summary: 'Register a new staff member (Guide/Porter)',
        tags: ['Staff (Mitra)'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'role', 'telepon'],
                properties: [
                    new OA\Property(property: 'basecamp_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'nama', type: 'string', example: 'Ahmad Porter'),
                    new OA\Property(property: 'role', type: 'string', enum: ['guide', 'porter', 'petugas'], example: 'porter'),
                    new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'is_available', type: 'boolean', example: true),
                    new OA\Property(property: 'jadwal_tugas', type: 'string', nullable: true, example: 'Senin - Jumat'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Staff member added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Staf baru berhasil ditambahkan.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MitraStaffResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreMitraStaffRequest $request): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $validated = $request->validated();
        $validated['mitra_id'] = $mitra->id;

        $staff = MitraStaff::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Staf baru berhasil ditambahkan.',
            'data' => new MitraStaffResource($staff),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/mitra/staff/{id}',
        summary: 'Get staff detail',
        tags: ['Staff (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff details fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MitraStaffResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Staff not found'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;
        $staff = MitraStaff::where('mitra_id', $mitra?->id)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail staf berhasil dimuat.',
            'data' => new MitraStaffResource($staff),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/mitra/staff/{id}',
        summary: 'Update staff details',
        tags: ['Staff (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff details updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Data staf berhasil diperbarui.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/MitraStaffResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateMitraStaffRequest $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;
        $staff = MitraStaff::where('mitra_id', $mitra?->id)->findOrFail($id);

        $staff->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data staf berhasil diperbarui.',
            'data' => new MitraStaffResource($staff),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/mitra/staff/{id}',
        summary: 'Delete a staff member',
        tags: ['Staff (Mitra)'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff member deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Staf berhasil dihapus.'),
                        new OA\Property(property: 'data', type: 'null', example: null),
                    ]
                )
            ),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;
        $staff = MitraStaff::where('mitra_id', $mitra?->id)->findOrFail($id);

        $staff->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Staf berhasil dihapus.',
            'data' => null,
        ]);
    }
}
