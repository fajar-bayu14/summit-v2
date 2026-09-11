<?php

namespace App\Http\Controllers\Pendaki;

use App\Http\Controllers\Controller;
use App\Http\Resources\BasecampResource;
use App\Models\Basecamp;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BasecampController extends Controller
{
    #[OA\Get(
        path: '/api/v1/basecamps/{id}',
        summary: 'Get details of a specific basecamp for climbers (Pendaki/Public)',
        tags: ['Basecamp (Climber)'],
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
                        new OA\Property(property: 'message', type: 'string', example: 'Basecamp details fetched successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BasecampResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Basecamp not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $basecamp = Basecamp::with(['mitra', 'jalur.gunung'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Basecamp details fetched successfully.',
            'data' => new BasecampResource($basecamp),
        ]);
    }
}
