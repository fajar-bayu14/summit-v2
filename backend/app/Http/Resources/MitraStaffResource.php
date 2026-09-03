<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MitraStaffResource',
    title: 'Mitra Staff Resource',
    description: 'Representation of Porter/Guide staff resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'mitra_id', type: 'integer', example: 1),
        new OA\Property(property: 'basecamp_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'nama', type: 'string', example: 'Ahmad Porter'),
        new OA\Property(property: 'role', type: 'string', enum: ['guide', 'porter', 'petugas'], example: 'porter'),
        new OA\Property(property: 'telepon', type: 'string', example: '081234567890'),
        new OA\Property(property: 'is_available', type: 'boolean', example: true),
        new OA\Property(property: 'jadwal_tugas', type: 'string', nullable: true, example: 'Senin - Jumat'),
    ]
)]
class MitraStaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mitra_id' => $this->mitra_id,
            'basecamp_id' => $this->basecamp_id,
            'nama' => $this->nama,
            'role' => $this->role,
            'telepon' => $this->telepon,
            'is_available' => (bool) $this->is_available,
            'jadwal_tugas' => $this->jadwal_tugas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
