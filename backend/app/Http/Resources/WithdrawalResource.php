<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WithdrawalResource',
    title: 'Withdrawal Resource',
    description: 'Representation of Mitra Payout / Withdrawal Request',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'mitra_id', type: 'integer', example: 1),
        new OA\Property(property: 'nominal', type: 'number', format: 'float', example: 500000.00),
        new OA\Property(property: 'biaya_admin', type: 'number', format: 'float', example: 0.00),
        new OA\Property(property: 'bank', type: 'string', example: 'BCA'),
        new OA\Property(property: 'rekening_bank', type: 'string', example: '1234567890'),
        new OA\Property(property: 'nama_rekening', type: 'string', example: 'Budi Santoso'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'], example: 'pending'),
        new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Penarikan mingguan'),
        new OA\Property(property: 'alasan_penolakan', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'failure_reason', type: 'string', nullable: true, example: null),
    ]
)]
class WithdrawalResource extends JsonResource
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
            'disbursement_id' => $this->disbursement_id,
            'nominal' => (float) $this->nominal,
            'biaya_admin' => (float) $this->biaya_admin,
            'bank' => $this->bank,
            'rekening_bank' => $this->rekening_bank,
            'nama_rekening' => $this->nama_rekening,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'alasan_penolakan' => $this->alasan_penolakan,
            'failure_reason' => $this->failure_reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
