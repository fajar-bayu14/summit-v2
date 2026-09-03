<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get the active cart for a user, creating one if none exists.
     */
    public function getOrCreateActiveCart(User $user, array $data = []): Cart
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.produk'])
            ->latest()
            ->first();

        if ($cart && empty($data)) {
            return $cart;
        }

        if (! $cart) {
            return $this->createCart($user, $data);
        }

        // Cart exists but a different basecamp/jalur/date was requested.
        $changed = array_key_exists('basecamp_id', $data) && (int) $data['basecamp_id'] !== (int) $cart->basecamp_id
            || array_key_exists('jalur_id', $data) && (int) $data['jalur_id'] !== (int) $cart->jalur_id
            || array_key_exists('tanggal_booking', $data) && $data['tanggal_booking'] !== $cart->tanggal_booking->toDateString();

        if ($changed) {
            $cart->update([
                'basecamp_id' => $data['basecamp_id'] ?? $cart->basecamp_id,
                'jalur_id' => $data['jalur_id'] ?? $cart->jalur_id,
                'tanggal_booking' => $data['tanggal_booking'] ?? $cart->tanggal_booking->toDateString(),
                'tanggal_selesai_booking' => $data['tanggal_selesai_booking'] ?? $cart->tanggal_selesai_booking,
            ]);

            // Items from another basecamp are no longer valid.
            $cart->items()->whereHas('produk', fn ($query) => $query->where('basecamp_id', '!=', $cart->basecamp_id))->delete();
        }

        return $cart->load('items.produk');
    }

    /**
     * Create a fresh cart scoped to a basecamp, trail, and booking date.
     */
    public function createCart(User $user, array $data): Cart
    {
        return Cart::create([
            'user_id' => $user->id,
            'basecamp_id' => $data['basecamp_id'],
            'jalur_id' => $data['jalur_id'],
            'tanggal_booking' => $data['tanggal_booking'],
            'tanggal_selesai_booking' => $data['tanggal_selesai_booking'] ?? null,
            'status' => 'active',
        ]);
    }

    /**
     * Add or merge a product into the user's active cart.
     *
     * @throws ValidationException
     */
    public function addItem(User $user, array $data): CartItem
    {
        $produk = Produk::where('id', $data['produk_id'])
            ->where('is_active', true)
            ->first();

        if (! $produk) {
            throw ValidationException::withMessages([
                'produk_id' => ['Produk tidak ditemukan atau tidak aktif.'],
            ]);
        }

        $cart = $this->getOrCreateActiveCart($user, [
            'basecamp_id' => $produk->basecamp_id,
            'jalur_id' => $data['jalur_id'],
            'tanggal_booking' => $data['tanggal_booking'],
            'tanggal_selesai_booking' => $data['tanggal_selesai_booking'] ?? null,
        ]);

        if ((int) $cart->basecamp_id !== (int) $produk->basecamp_id) {
            throw ValidationException::withMessages([
                'produk_id' => ['Produk ini berasal dari basecamp yang berbeda dengan keranjang aktif Anda.'],
            ]);
        }

        $this->assertAvailability($produk, (int) $data['qty'], $data['jalur_id']);

        $item = $cart->items()->where('produk_id', $produk->id)->first();

        if ($item) {
            $item->update([
                'qty' => $item->qty + (int) $data['qty'],
                'tanggal_mulai_sewa' => $data['tanggal_mulai_sewa'] ?? $item->tanggal_mulai_sewa,
                'tanggal_selesai_sewa' => $data['tanggal_selesai_sewa'] ?? $item->tanggal_selesai_sewa,
                'catatan_item' => $data['catatan_item'] ?? $item->catatan_item,
            ]);

            return $item;
        }

        return $cart->items()->create([
            'produk_id' => $produk->id,
            'qty' => (int) $data['qty'],
            'tanggal_mulai_sewa' => $data['tanggal_mulai_sewa'] ?? null,
            'tanggal_selesai_sewa' => $data['tanggal_selesai_sewa'] ?? null,
            'catatan_item' => $data['catatan_item'] ?? null,
        ]);
    }

    /**
     * Update the quantity or rental dates of an item in the user's active cart.
     *
     * @throws ValidationException
     */
    public function updateItem(User $user, int $itemId, array $data): CartItem
    {
        $cart = $this->getActiveCartOrFail($user);
        $item = $cart->items()->find($itemId);

        if (! $item) {
            throw ValidationException::withMessages([
                'cart_item' => ['Item keranjang tidak ditemukan.'],
            ]);
        }

        if (array_key_exists('qty', $data)) {
            $this->assertAvailability($item->produk, (int) $data['qty'], $cart->jalur_id);
            $item->qty = (int) $data['qty'];
        }

        if (array_key_exists('tanggal_mulai_sewa', $data)) {
            $item->tanggal_mulai_sewa = $data['tanggal_mulai_sewa'];
        }

        if (array_key_exists('tanggal_selesai_sewa', $data)) {
            $item->tanggal_selesai_sewa = $data['tanggal_selesai_sewa'];
        }

        if (array_key_exists('catatan_item', $data)) {
            $item->catatan_item = $data['catatan_item'];
        }

        $item->save();

        return $item;
    }

    /**
     * Remove an item from the user's active cart.
     */
    public function removeItem(User $user, int $itemId): void
    {
        $cart = $this->getActiveCartOrFail($user);
        $cart->items()->where('id', $itemId)->delete();
    }

    /**
     * Empty the user's active cart.
     */
    public function clear(User $user): void
    {
        Cart::where('user_id', $user->id)->where('status', 'active')->delete();
    }

    /**
     * Mark the cart as checked out.
     */
    public function checkoutCart(Cart $cart): void
    {
        $cart->update(['status' => 'checked_out']);
    }

    /**
     * Ensure a product still has stock / daily quota for the requested quantity.
     *
     * @throws ValidationException
     */
    protected function assertAvailability(Produk $produk, int $qty, int $jalurId): void
    {
        if ($produk->kategori === 'ticket') {
            if (! $produk->tiket || $produk->tiket->jalur_id !== $jalurId) {
                throw ValidationException::withMessages([
                    'produk_id' => ["Produk tiket {$produk->nama_produk} tidak sesuai dengan jalur pendakian yang dipilih."],
                ]);
            }
        } elseif ($produk->stok !== null && $produk->stok < $qty) {
            throw ValidationException::withMessages([
                'qty' => ["Stok produk {$produk->nama_produk} tidak mencukupi (Tersisa: {$produk->stok})."],
            ]);
        }
    }

    /**
     * Get the user's active cart or throw a validation error.
     *
     * @throws ValidationException
     */
    protected function getActiveCartOrFail(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('items.produk')
            ->latest()
            ->first();

        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => ['Keranjang belanja masih kosong. Tambahkan item terlebih dahulu.'],
            ]);
        }

        return $cart;
    }
}
