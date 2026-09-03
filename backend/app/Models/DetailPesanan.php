<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pesanan_id',
    'produk_id',
    'qty',
    'harga',
    'subtotal',
    'status_operasional',
    'kode_tiket',
    'tanggal_mulai_sewa',
    'tanggal_selesai_sewa',
    'catatan_item',
])]
class DetailPesanan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'detail_pesanans';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tanggal_mulai_sewa' => 'date',
            'tanggal_selesai_sewa' => 'date',
            'catatan_item' => 'array',
        ];
    }

    /**
     * Get the parent order.
     *
     * @return BelongsTo<Pesanan, DetailPesanan>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }

    /**
     * Get the product purchased.
     *
     * @return BelongsTo<Produk, DetailPesanan>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
