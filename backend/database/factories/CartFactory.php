<?php

namespace Database\Factories;

use App\Models\Basecamp;
use App\Models\Cart;
use App\Models\JalurPendakian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'basecamp_id' => Basecamp::factory(),
            'jalur_id' => JalurPendakian::factory(),
            'tanggal_booking' => fake()->date(),
            'tanggal_selesai_booking' => null,
            'status' => 'active',
        ];
    }
}
