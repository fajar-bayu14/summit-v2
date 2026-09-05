<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mitra\UpdateMitraProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Put(
        path: '/api/v1/mitra/profile',
        summary: 'Update partner (Mitra) profile details and disbursement accounts',
        tags: ['Mitra Profile'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_pemilik', 'telepon', 'alamat', 'nik', 'rekening_bank', 'nama_rekening', 'bank'],
                properties: [
                    new OA\Property(property: 'nama_pemilik', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Raya Summit No. 10'),
                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Pengelola Basecamp Merbabu'),
                    new OA\Property(property: 'npwp', type: 'string', nullable: true, example: '12.345.678.9-012.000'),
                    new OA\Property(property: 'nik', type: 'string', example: '3201234567890001'),
                    new OA\Property(property: 'rekening_bank', type: 'string', example: '1234567890'),
                    new OA\Property(property: 'nama_rekening', type: 'string', example: 'Budi Santoso'),
                    new OA\Property(property: 'bank', type: 'string', example: 'Bank BCA'),
                    new OA\Property(property: 'ewallet', type: 'string', nullable: true, example: '081234567890'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mitra profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil mitra berhasil diperbarui.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProfileResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - only for role mitra'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateMitraProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $mitra = $user->mitra;

        if (! $mitra) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil mitra tidak ditemukan.',
            ], 403);
        }

        $mitra->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Profil mitra berhasil diperbarui.',
            'data' => new ProfileResource($user->fresh(['mitra.basecamps.jalur.gunung', 'mitra.staff'])),
        ]);
    }
}
