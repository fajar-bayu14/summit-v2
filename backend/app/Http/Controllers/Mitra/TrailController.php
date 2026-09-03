<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Resources\JalurPendakianResource;
use App\Models\JalurPendakian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TrailController extends Controller
{
    #[OA\Post(
        path: '/api/v1/mitra/trails/{id}/emergency-close',
        summary: 'Emergency close/open a trail operated by this partner',
        tags: ['Trail (Mitra)'],
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
                    new OA\Property(property: 'alasan_penutupan', type: 'string', example: 'Badai dan pohon tumbang di pos 2'),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Status jalur berhasil diperbarui.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/JalurPendakianResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function emergencyClose(Request $request, int $id): JsonResponse
    {
        $mitra = $request->user()->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        // Verify that this partner actually operates a basecamp for this trail
        $hasAccess = $mitra->basecamps()->where('jalur_id', $id)->exists();
        if (! $hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk mengelola jalur ini.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,close'],
            'alasan_penutupan' => ['nullable', 'string', 'max:500'],
        ]);

        $jalur = JalurPendakian::findOrFail($id);
        $jalur->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status jalur berhasil diperbarui.',
            'data' => new JalurPendakianResource($jalur->load('gunung')),
        ]);
    }
}
