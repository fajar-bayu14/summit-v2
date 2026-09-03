<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartResource',
    title: 'Cart Resource',
    description: 'Shopping cart resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'basecamp_id', type: 'integer', example: 2),
        new OA\Property(property: 'jalur_id', type: 'integer', example: 3),
        new OA\Property(property: 'tanggal_booking', type: 'string', format: 'date', example: '2026-08-10'),
        new OA\Property(property: 'tanggal_selesai_booking', type: 'string', format: 'date', nullable: true, example: '2026-08-12'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'total_item', type: 'integer', example: 3),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 75000.00),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItemResource')),
    ]
)]
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        $subtotal = 0.00;
        $totalItem = 0;

        if ($items) {
            foreach ($items as $item) {
                $subtotal += (float) $item->produk->harga * $item->qty;
                $totalItem += $item->qty;
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'basecamp_id' => $this->basecamp_id,
            'jalur_id' => $this->jalur_id,
            'tanggal_booking' => $this->tanggal_booking?->toDateString(),
            'tanggal_selesai_booking' => $this->tanggal_selesai_booking?->toDateString(),
            'status' => $this->status,
            'total_item' => $totalItem,
            'subtotal' => round($subtotal, 2),
            'items' => CartItemResource::collection($items),
        ];
    }
}
