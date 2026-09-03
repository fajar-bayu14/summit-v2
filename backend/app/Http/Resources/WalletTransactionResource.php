<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WalletTransactionResource',
    title: 'Wallet Transaction (Ledger) Resource',
    description: 'Representation of Escrow Ledger Transaction',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
        new OA\Property(property: 'pesanan_id', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'type', type: 'string', enum: ['inflow_holding', 'release_to_available', 'withdrawal_lock', 'withdrawal_settled', 'withdrawal_refunded', 'refund_deduction'], example: 'inflow_holding'),
        new OA\Property(property: 'nominal', type: 'number', format: 'float', example: 75000.00),
        new OA\Property(property: 'saldo_pending_after', type: 'number', format: 'float', example: 75000.00),
        new OA\Property(property: 'saldo_available_after', type: 'number', format: 'float', example: 0.00),
        new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Dana pesanan masuk ke escrow holding: INV/20260903/ABC'),
    ]
)]
class WalletTransactionResource extends JsonResource
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
            'wallet_id' => $this->wallet_id,
            'pesanan_id' => $this->pesanan_id,
            'pesanan_invoice' => $this->pesanan?->invoice,
            'type' => $this->type,
            'nominal' => (float) $this->nominal,
            'saldo_pending_after' => (float) $this->saldo_pending_after,
            'saldo_available_after' => (float) $this->saldo_available_after,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
