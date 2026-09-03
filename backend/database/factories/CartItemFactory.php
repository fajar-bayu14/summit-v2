<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'produk_id' => Produk::factory(),
            'qty' => fake()->numberBetween(1, 5),
            'tanggal_mulai_sewa' => null,
            'tanggal_selesai_sewa' => null,
            'catatan_item' => null,
        ];
    }
}
