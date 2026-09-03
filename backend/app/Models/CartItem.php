<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cart_id',
    'produk_id',
    'qty',
    'tanggal_mulai_sewa',
    'tanggal_selesai_sewa',
    'catatan_item',
])]
class CartItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'tanggal_mulai_sewa' => 'date',
            'tanggal_selesai_sewa' => 'date',
            'catatan_item' => 'array',
        ];
    }

    /**
     * Get the cart that owns this item.
     *
     * @return BelongsTo<Cart, CartItem>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Get the product being added to the cart.
     *
     * @return BelongsTo<Produk, CartItem>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
