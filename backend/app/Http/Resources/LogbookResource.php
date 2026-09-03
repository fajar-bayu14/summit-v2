<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LogbookResource',
    title: 'Digital Logbook Resource',
    description: 'Summit proof and climbing logbook record',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pesanan_id', type: 'integer', example: 10),
        new OA\Property(property: 'invoice', type: 'string', example: 'INV/20260903/ABC123'),
        new OA\Property(property: 'nama_pendaki', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'gunung_nama', type: 'string', example: 'Gunung Gede'),
        new OA\Property(property: 'jalur_nama', type: 'string', example: 'Jalur Cibodas'),
        new OA\Property(property: 'tinggi_mdpl', type: 'integer', example: 2958),
        new OA\Property(property: 'foto_summit', type: 'string', example: 'http://localhost:8000/storage/summit_proofs/photo.jpg'),
        new OA\Property(property: 'latitude', type: 'string', nullable: true, example: '-6.7900'),
        new OA\Property(property: 'longitude', type: 'string', nullable: true, example: '107.0000'),
        new OA\Property(property: 'waktu_summit', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'catatan_pendaki', type: 'string', nullable: true, example: 'Cuaca cerah di puncak.'),
        new OA\Property(property: 'status_validasi', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'catatan_petugas', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'validated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class LogbookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pesanan = $this->pesanan;
        $jalur = $pesanan?->jalur;
        $gunung = $jalur?->gunung;

        return [
            'id' => $this->id,
            'pesanan_id' => $this->pesanan_id,
            'invoice' => $pesanan?->invoice,
            'user_id' => $this->user_id,
            'nama_pendaki' => $this->user?->name,
            'gunung_nama' => $gunung?->nama_gunung,
            'jalur_nama' => $jalur?->nama_jalur,
            'tinggi_mdpl' => $gunung?->tinggi_mdpl,
            'foto_summit' => $this->foto_summit ? asset('storage/'.$this->foto_summit) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'waktu_summit' => $this->waktu_summit?->toISOString(),
            'catatan_pendaki' => $this->catatan_pendaki,
            'status_validasi' => $this->status_validasi,
            'catatan_petugas' => $this->catatan_petugas,
            'validated_at' => $this->validated_at?->toISOString(),
            'validated_by' => $this->validator?->name,
            'certificate_url' => $this->status_validasi === 'approved' && $pesanan?->invoice
                ? url('/api/v1/orders/'.$pesanan->invoice.'/certificate')
                : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
