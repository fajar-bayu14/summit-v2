<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BannerAdResource',
    title: 'Banner Ad Resource',
    description: 'Promotional Banner Advertisement Slot',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'mitra_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'mitra_nama', type: 'string', nullable: true, example: 'Toko Outdoor Summit'),
        new OA\Property(property: 'judul', type: 'string', example: 'Promo Sewa Alat Pendakian Diskon 20%'),
        new OA\Property(property: 'gambar', type: 'string', example: 'http://localhost:8000/storage/banners/promo.jpg'),
        new OA\Property(property: 'link_url', type: 'string', nullable: true, example: 'https://summit.id/promo/gear'),
        new OA\Property(property: 'posisi', type: 'string', enum: ['home_top', 'mountain_detail', 'search_sidebar'], example: 'home_top'),
        new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-09-30'),
        new OA\Property(property: 'total_impressions', type: 'integer', example: 1250),
        new OA\Property(property: 'total_clicks', type: 'integer', example: 85),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ]
)]
class BannerAdResource extends JsonResource
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
            'mitra_nama' => $this->mitra?->nama_pemilik,
            'judul' => $this->judul,
            'gambar' => $this->gambar ? asset('storage/'.$this->gambar) : null,
            'link_url' => $this->link_url,
            'posisi' => $this->posisi,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'total_impressions' => (int) $this->total_impressions,
            'total_clicks' => (int) $this->total_clicks,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
