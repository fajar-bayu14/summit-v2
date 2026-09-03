<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'basecamp_id',
    'jalur_id',
    'tanggal_booking',
    'tanggal_selesai_booking',
    'status',
])]
class Cart extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'carts';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_booking' => 'date',
            'tanggal_selesai_booking' => 'date',
        ];
    }

    /**
     * Get the climber who owns the cart.
     *
     * @return BelongsTo<User, Cart>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the basecamp the cart is scoped to.
     *
     * @return BelongsTo<Basecamp, Cart>
     */
    public function basecamp(): BelongsTo
    {
        return $this->belongsTo(Basecamp::class, 'basecamp_id');
    }

    /**
     * Get the trail the cart is scoped to.
     *
     * @return BelongsTo<JalurPendakian, Cart>
     */
    public function jalur(): BelongsTo
    {
        return $this->belongsTo(JalurPendakian::class, 'jalur_id');
    }

    /**
     * Get the items in the cart.
     *
     * @return HasMany<CartItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }
}
