<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BadgeResource',
    title: 'Climbing Achievement Badge Resource',
    description: 'Summit Conqueror Badge representation for Climber Profile',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'badge_title', type: 'string', example: 'Penakluk Gunung Gede'),
        new OA\Property(property: 'gunung_nama', type: 'string', example: 'Gunung Gede'),
        new OA\Property(property: 'tinggi_mdpl', type: 'integer', example: 2958),
        new OA\Property(property: 'lokasi', type: 'string', example: 'Cianjur, Jawa Barat'),
        new OA\Property(property: 'tanggal_summit', type: 'string', format: 'date-time'),
        new OA\Property(property: 'certificate_url', type: 'string', example: 'http://localhost:8000/api/v1/orders/INV-123/certificate'),
    ]
)]
class BadgeResource extends JsonResource
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
            'badge_title' => 'Penakluk '.($gunung?->nama_gunung ?? 'Puncak'),
            'gunung_nama' => $gunung?->nama_gunung,
            'tinggi_mdpl' => $gunung?->tinggi_mdpl,
            'lokasi' => $gunung?->lokasi,
            'foto_gunung' => $gunung?->foto ? asset('storage/'.$gunung->foto) : null,
            'tanggal_summit' => ($this->waktu_summit ?? $this->validated_at)?->toISOString(),
            'certificate_url' => $pesanan?->invoice
                ? url('/api/v1/orders/'.$pesanan->invoice.'/certificate')
                : null,
        ];
    }
}
