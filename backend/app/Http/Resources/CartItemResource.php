<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartItemResource',
    title: 'Cart Item Resource',
    description: 'Cart line item resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cart_id', type: 'integer', example: 10),
        new OA\Property(property: 'produk_id', type: 'integer', example: 2),
        new OA\Property(property: 'qty', type: 'integer', example: 2),
        new OA\Property(property: 'tanggal_mulai_sewa', type: 'string', format: 'date', nullable: true, example: '2026-08-10'),
        new OA\Property(property: 'tanggal_selesai_sewa', type: 'string', format: 'date', nullable: true, example: '2026-08-12'),
        new OA\Property(property: 'catatan_item', type: 'object', nullable: true),
        new OA\Property(property: 'produk', ref: '#/components/schemas/ProdukResource'),
    ]
)]
class CartItemResource extends JsonResource
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
            'cart_id' => $this->cart_id,
            'produk_id' => $this->produk_id,
            'qty' => $this->qty,
            'tanggal_mulai_sewa' => $this->tanggal_mulai_sewa?->toDateString(),
            'tanggal_selesai_sewa' => $this->tanggal_selesai_sewa?->toDateString(),
            'catatan_item' => $this->catatan_item,
            'produk' => new ProdukResource($this->whenLoaded('produk')),
        ];
    }
}
