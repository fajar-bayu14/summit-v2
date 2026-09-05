<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RefundResource',
    title: 'Refund Resource',
    description: 'Representation of Transaction Refund',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'pesanan_id', type: 'integer', example: 10),
        new OA\Property(property: 'pesanan_invoice', type: 'string', example: 'INV/20260903/ABC123'),
        new OA\Property(property: 'nominal', type: 'number', format: 'float', example: 95000.00),
        new OA\Property(property: 'tipe', type: 'string', enum: ['auto', 'manual'], example: 'manual'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'success', 'failed'], example: 'pending'),
        new OA\Property(property: 'alasan', type: 'string', example: 'Penutupan jalur darurat cuaca ekstrem'),
        new OA\Property(property: 'bank_tujuan', type: 'string', nullable: true, example: 'BCA'),
        new OA\Property(property: 'rekening_tujuan', type: 'string', nullable: true, example: '1234567890'),
        new OA\Property(property: 'nama_tujuan', type: 'string', nullable: true, example: 'John Doe'),
    ]
)]
class RefundResource extends JsonResource
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
            'pesanan_id' => $this->pesanan_id,
            'pesanan_invoice' => $this->pesanan?->invoice,
            'mitra_id' => $this->mitra_id,
            'pembayaran_id' => $this->pembayaran_id,
            'refund_category' => $this->refund_category,
            'nominal' => (float) $this->nominal,
            'nominal_disetujui' => $this->nominal_disetujui !== null ? (float) $this->nominal_disetujui : null,
            'tipe' => $this->tipe,
            'status' => $this->status,
            'alasan' => $this->alasan,
            'bank_tujuan' => $this->bank_tujuan,
            'rekening_tujuan' => $this->rekening_tujuan,
            'nama_tujuan' => $this->nama_tujuan,
            'bukti_transfer' => $this->bukti_transfer,
            'mitra_alasan_penolakan' => $this->mitra_alasan_penolakan,
            'mitra_reviewed_at' => $this->mitra_reviewed_at?->toISOString(),
            'is_disputed' => (bool) $this->is_disputed,
            'disputed_at' => $this->disputed_at?->toISOString(),
            'dispute_reason' => $this->dispute_reason,
            'admin_catatan' => $this->admin_catatan,
            'pesanan' => new PesananResource($this->whenLoaded('pesanan')),
            'mitra' => new MitraResource($this->whenLoaded('mitra')),
            'refunded_at' => $this->refunded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
