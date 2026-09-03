<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MitraWalletResource',
    title: 'Mitra Wallet Resource',
    description: 'Representation of Partner Escrow & Available Balance',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'mitra_id', type: 'integer', example: 1),
        new OA\Property(property: 'saldo_pending', type: 'number', format: 'float', example: 350000.00),
        new OA\Property(property: 'saldo_available', type: 'number', format: 'float', example: 1500000.00),
        new OA\Property(property: 'total_withdrawn', type: 'number', format: 'float', example: 5000000.00),
        new OA\Property(property: 'total_terhitung', type: 'number', format: 'float', example: 1850000.00),
    ]
)]
class MitraWalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $saldoPending = (float) $this->saldo_pending;
        $saldoAvailable = (float) $this->saldo_available;

        return [
            'id' => $this->id,
            'mitra_id' => $this->mitra_id,
            'saldo_pending' => $saldoPending,
            'saldo_available' => $saldoAvailable,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'total_terhitung' => $saldoPending + $saldoAvailable,
            'rekening_tujuan' => [
                'bank' => $this->mitra?->bank,
                'rekening_bank' => $this->mitra?->rekening_bank,
                'nama_rekening' => $this->mitra?->nama_rekening,
            ],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
