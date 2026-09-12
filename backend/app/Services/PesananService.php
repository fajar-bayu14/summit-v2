<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\KuotaHarianTiket;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PesananService
{
    /**
     * Create a new booking transaction.
     *
     * @throws ValidationException
     */
    public function createPesanan(User $user, array $data): Pesanan
    {
        return $this->withUserLock($user, function () use ($user, $data) {
            return DB::transaction(function () use ($user, $data) {
                [$subtotal, $detailsData] = $this->lockStockAndBuildDetails(
                    (int) $data['basecamp_id'],
                    (int) $data['jalur_id'],
                    $data['tanggal_booking'],
                    $data['items']
                );

                $pesanan = $this->persistPesanan(
                    $user,
                    (int) $data['basecamp_id'],
                    (int) $data['jalur_id'],
                    $data['tanggal_booking'],
                    null,
                    $subtotal,
                    $detailsData,
                    $data['anggotas']
                );

                $pesanan->load(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran']);

                return $pesanan;
            });
        });
    }

    /**
     * Checkout the persisted cart into a pending order and initial payment record.
     *
     * @throws ValidationException
     */
    public function checkout(Cart $cart, array $anggotaData): Pesanan
    {
        return $this->withUserLock($cart->user, function () use ($cart, $anggotaData) {
            return DB::transaction(function () use ($cart, $anggotaData) {
                $items = $cart->items()->with('produk')->get();

                if ($items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => ['Keranjang belanja kosong, tidak ada item untuk diproses.'],
                    ]);
                }

                $itemPayloads = $items->map(function ($item) {
                    return [
                        'produk_id' => $item->produk_id,
                        'qty' => $item->qty,
                        'tanggal_mulai_sewa' => $item->tanggal_mulai_sewa,
                        'tanggal_selesai_sewa' => $item->tanggal_selesai_sewa,
                        'catatan_item' => $item->catatan_item,
                    ];
                })->all();

                [$subtotal, $detailsData] = $this->lockStockAndBuildDetails(
                    (int) $cart->basecamp_id,
                    (int) $cart->jalur_id,
                    $cart->tanggal_booking->toDateString(),
                    $itemPayloads
                );

                $pesanan = $this->persistPesanan(
                    $cart->user,
                    (int) $cart->basecamp_id,
                    (int) $cart->jalur_id,
                    $cart->tanggal_booking->toDateString(),
                    $cart->tanggal_selesai_booking?->toDateString(),
                    $subtotal,
                    $detailsData,
                    $anggotaData
                );

                $cart->update(['status' => 'checked_out']);

                $pesanan->load(['user', 'basecamp', 'jalur', 'anggotas', 'details.produk', 'pembayaran']);

                return $pesanan;
            });
        });
    }

    /**
     * Mark an order and its payment as paid (triggered by the webhook).
     */
    public function markPaid(Pesanan $pesanan, array $payload): bool
    {
        return DB::transaction(function () use ($pesanan, $payload) {
            $pesanan = Pesanan::where('id', $pesanan->id)
                ->with(['details.produk.tiket', 'basecamp.mitra'])
                ->lockForUpdate()
                ->first();

            if (! $pesanan) {
                return false;
            }

            // Idempotency: jika sudah paid, return true
            if ($pesanan->status === 'paid') {
                return true;
            }

            $pembayaran = $pesanan->pembayaran()->lockForUpdate()->first();

            if ($pesanan->status === 'expired') {
                return $this->handleLatePayment($pesanan, $pembayaran, $payload);
            }

            if ($pesanan->status !== 'pending') {
                return false;
            }

            $pesanan->update(['status' => 'paid']);

            $pembayaran?->update([
                'metode' => $this->mapPaymentMethod($payload['payment_method'] ?? null),
                'provider' => $payload['payment_channel'] ?? 'Xendit',
                'reference_id' => $payload['id'] ?? $pembayaran->reference_id,
                'paid_amount' => $payload['paid_amount'] ?? $pembayaran->amount,
                'status' => 'success',
                'raw_response' => $payload,
                'paid_at' => now(),
            ]);

            $pesanan->details()->update(['status_operasional' => 'ready']);

            app(EscrowService::class)->recordPaymentHolding($pesanan);

            return true;
        });
    }

    /**
     * Handle payment received after the order has already been marked expired.
     */
    protected function handleLatePayment(Pesanan $pesanan, ?Pembayaran $pembayaran, array $payload): bool
    {
        $canReopen = true;

        foreach ($pesanan->details as $detail) {
            $produk = $detail->produk;
            if (! $produk) {
                continue;
            }

            if ($produk->kategori === 'ticket') {
                $produkTiket = $produk->tiket;
                if ($produkTiket) {
                    $kuota = KuotaHarianTiket::where('produk_tiket_id', $produkTiket->id)
                        ->where('tanggal', $pesanan->tanggal_booking->toDateString())
                        ->lockForUpdate()
                        ->first();

                    if (! $kuota || $kuota->kuota_tersisa < $detail->qty) {
                        $canReopen = false;
                        break;
                    }
                }
            } elseif ($produk->stok !== null) {
                $produkLocked = Produk::where('id', $produk->id)->lockForUpdate()->first();
                if (! $produkLocked || $produkLocked->stok < $detail->qty) {
                    $canReopen = false;
                    break;
                }
            }
        }

        if ($canReopen) {
            // Re-deduct quota and stock
            foreach ($pesanan->details as $detail) {
                $produk = $detail->produk;
                if (! $produk) {
                    continue;
                }

                if ($produk->kategori === 'ticket') {
                    $produkTiket = $produk->tiket;
                    if ($produkTiket) {
                        KuotaHarianTiket::where('produk_tiket_id', $produkTiket->id)
                            ->where('tanggal', $pesanan->tanggal_booking->toDateString())
                            ->decrement('kuota_tersisa', $detail->qty);
                    }
                } elseif ($produk->stok !== null) {
                    Produk::where('id', $produk->id)->decrement('stok', $detail->qty);
                }
            }

            $pesanan->update(['status' => 'paid']);
            $pembayaran?->update([
                'metode' => $this->mapPaymentMethod($payload['payment_method'] ?? null),
                'provider' => $payload['payment_channel'] ?? 'Xendit',
                'reference_id' => $payload['id'] ?? $pembayaran->reference_id,
                'paid_amount' => $payload['paid_amount'] ?? $pembayaran->amount,
                'status' => 'success',
                'raw_response' => $payload,
                'paid_at' => now(),
            ]);

            $pesanan->details()->update(['status_operasional' => 'ready']);
            app(EscrowService::class)->recordPaymentHolding($pesanan);

            Log::info("Late payment recovered: booking {$pesanan->invoice} reopened successfully.");

            return true;
        }

        // Quota sold out: Record payment as success in platform suspense, keep order expired for refund resolution
        $pembayaran?->update([
            'metode' => $this->mapPaymentMethod($payload['payment_method'] ?? null),
            'provider' => $payload['payment_channel'] ?? 'Xendit',
            'reference_id' => $payload['id'] ?? $pembayaran->reference_id,
            'paid_amount' => $payload['paid_amount'] ?? $pembayaran->amount,
            'status' => 'success',
            'raw_response' => $payload,
            'paid_at' => now(),
        ]);

        Log::warning("Late payment received for expired order {$pesanan->invoice}, but quota/stock is exhausted. Order retained as expired, routed to refund/reconciliation.");

        return true;
    }

    /**
     * Mark an order as expired (webhook or scheduled expiry) and restore stock.
     */
    public function markExpired(Pesanan $pesanan): bool
    {
        return DB::transaction(function () use ($pesanan) {
            $pesanan = Pesanan::where('id', $pesanan->id)
                ->with(['details.produk', 'details.produk.tiket'])
                ->lockForUpdate()
                ->first();

            if (! $pesanan || $pesanan->status !== 'pending') {
                return false;
            }

            $pembayaran = $pesanan->pembayaran()->lockForUpdate()->first();

            $pesanan->update(['status' => 'expired']);
            $pembayaran?->update(['status' => 'expired']);

            $this->restoreStock($pesanan);

            return true;
        });
    }

    /**
     * Cancel a pending order by the climber or admin and restore stock.
     */
    public function cancel(Pesanan $pesanan): bool
    {
        return DB::transaction(function () use ($pesanan) {
            $pesanan = Pesanan::where('id', $pesanan->id)
                ->with(['details.produk', 'details.produk.tiket'])
                ->lockForUpdate()
                ->first();

            if (! $pesanan || $pesanan->status !== 'pending') {
                return false;
            }

            $pembayaran = $pesanan->pembayaran()->lockForUpdate()->first();

            $pesanan->update(['status' => 'cancelled']);
            $pembayaran?->update(['status' => 'cancelled']);

            $this->restoreStock($pesanan);

            return true;
        });
    }

    /**
     * Expire all pending orders whose payment window has passed.
     */
    public function expireStale(): int
    {
        $expired = 0;

        $candidateIds = Pesanan::where('status', 'pending')
            ->whereHas('pembayaran', function ($query) {
                $query->where('expired_at', '<', now());
            })
            ->limit(100)
            ->pluck('id');

        foreach ($candidateIds as $id) {
            $success = DB::transaction(function () use ($id) {
                $pesanan = Pesanan::where('id', $id)
                    ->with(['details.produk', 'details.produk.tiket'])
                    ->lockForUpdate()
                    ->first();

                if (! $pesanan || $pesanan->status !== 'pending') {
                    return false;
                }

                $pembayaran = $pesanan->pembayaran()->lockForUpdate()->first();

                if (! $pembayaran || ! $pembayaran->expired_at?->isPast()) {
                    return false;
                }

                $pesanan->update(['status' => 'expired']);
                $pembayaran->update(['status' => 'expired']);

                $this->restoreStock($pesanan);

                return true;
            });

            if ($success) {
                $expired++;
            }
        }

        return $expired;
    }

    /**
     * Restore stock/quota previously held by an order's line items.
     */
    public function restoreStock(Pesanan $pesanan): void
    {
        foreach ($pesanan->details as $detail) {
            $produk = $detail->produk;

            if (! $produk) {
                continue;
            }

            if ($produk->kategori === 'ticket') {
                $produkTiket = $produk->tiket;

                if (! $produkTiket) {
                    continue;
                }

                KuotaHarianTiket::where('produk_tiket_id', $produkTiket->id)
                    ->where('tanggal', $pesanan->tanggal_booking->toDateString())
                    ->increment('kuota_tersisa', $detail->qty);
            } elseif ($produk->stok !== null) {
                $produk->increment('stok', $detail->qty);
            }
        }
    }

    /**
     * Persist the order, its line items, members, and initial payment row.
     *
     * @param  array<int, array<string, mixed>>  $detailsData
     * @param  array<int, array<string, mixed>>  $anggotaData
     */
    protected function persistPesanan(
        User $user,
        int $basecampId,
        int $jalurId,
        string $tanggalBooking,
        ?string $tanggalSelesaiBooking,
        float $subtotal,
        array $detailsData,
        array $anggotaData
    ): Pesanan {
        $biayaLayananUser = 5000.00; // Flat platform service fee
        $komisiAdmin = $subtotal * 0.10; // 10% admin fee
        $pendapatanMitra = $subtotal - $komisiAdmin;
        $totalBayar = $subtotal + $biayaLayananUser;

        $pesanan = Pesanan::create([
            'invoice' => $this->generateInvoice(),
            'user_id' => $user->id,
            'basecamp_id' => $basecampId,
            'jalur_id' => $jalurId,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tanggal_booking' => $tanggalBooking,
            'tanggal_selesai_booking' => $tanggalSelesaiBooking,
            'diskon' => 0.00,
            'biaya_layanan_user' => $biayaLayananUser,
            'komisi_admin' => $komisiAdmin,
            'pendapatan_mitra' => $pendapatanMitra,
            'total_bayar' => $totalBayar,
            'status_escrow' => 'holding',
        ]);

        foreach ($detailsData as $detail) {
            $pesanan->details()->create($detail);
        }

        foreach ($anggotaData as $anggota) {
            $pesanan->anggotas()->create([
                'nama_anggota' => $anggota['nama_anggota'],
                'nik_identitas' => $anggota['nik_identitas'],
                'telepon' => $anggota['telepon'] ?? null,
                'telepon_darurat' => $anggota['telepon_darurat'] ?? null,
                'hubungan_darurat' => $anggota['hubungan_darurat'] ?? null,
            ]);
        }

        $pesanan->pembayaran()->create([
            'amount' => $pesanan->total_bayar,
            'status' => 'pending',
        ]);

        return $pesanan;
    }

    /**
     * Lock the products/quota referenced by the items and build line item rows.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     *
     * @throws ValidationException
     */
    protected function lockStockAndBuildDetails(
        int $basecampId,
        int $jalurId,
        string $tanggalBooking,
        array $items
    ): array {
        $items = collect($items)->sortBy('produk_id')->values()->all();

        $subtotal = 0.00;
        $detailsData = [];

        foreach ($items as $item) {
            $produk = Produk::where('id', $item['produk_id'])
                ->where('basecamp_id', $basecampId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $produk) {
                throw ValidationException::withMessages([
                    'items' => ["Produk dengan ID {$item['produk_id']} tidak aktif atau tidak ditemukan di basecamp ini."],
                ]);
            }

            $qty = (int) $item['qty'];
            $harga = (float) $produk->harga;
            $itemSubtotal = $harga * $qty;
            $subtotal += $itemSubtotal;

            $kodeTiket = null;

            if ($produk->kategori === 'ticket') {
                $produkTiket = $produk->tiket;

                if (! $produkTiket || $produkTiket->jalur_id !== $jalurId) {
                    throw ValidationException::withMessages([
                        'items' => ["Produk tiket {$produk->nama_produk} tidak sesuai dengan jalur pendakian yang dipilih."],
                    ]);
                }

                $tanggalCarbon = Carbon::parse($tanggalBooking)->startOfDay();
                $kuota = KuotaHarianTiket::firstOrCreate(
                    [
                        'produk_tiket_id' => $produkTiket->id,
                        'tanggal' => $tanggalCarbon,
                    ],
                    [
                        'kuota_total' => 100,
                        'kuota_tersisa' => 100,
                    ]
                );

                $kuota = KuotaHarianTiket::where('id', $kuota->id)->lockForUpdate()->first();

                if ($kuota->kuota_tersisa < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Kuota tiket {$produk->nama_produk} untuk tanggal {$tanggalBooking} tidak mencukupi (Tersisa: {$kuota->kuota_tersisa})."],
                    ]);
                }

                $kuota->decrement('kuota_tersisa', $qty);

                $kodeTiket = 'TKT-'.strtoupper(Str::random(8));
            } else {
                if ($produk->stok !== null) {
                    if ($produk->stok < $qty) {
                        throw ValidationException::withMessages([
                            'items' => ["Stok produk {$produk->nama_produk} tidak mencukupi (Tersisa: {$produk->stok})."],
                        ]);
                    }

                    $produk->decrement('stok', $qty);
                }
            }

            $detailsData[] = [
                'produk_id' => $produk->id,
                'qty' => $qty,
                'harga' => $harga,
                'subtotal' => $itemSubtotal,
                'status_operasional' => 'pending',
                'kode_tiket' => $kodeTiket,
                'tanggal_mulai_sewa' => $item['tanggal_mulai_sewa'] ?? null,
                'tanggal_selesai_sewa' => $item['tanggal_selesai_sewa'] ?? null,
                'catatan_item' => $item['catatan_item'] ?? null,
            ];
        }

        return [$subtotal, $detailsData];
    }

    /**
     * Map a Xendit payment method to the internal payment method enum value.
     */
    protected function mapPaymentMethod(?string $method): ?string
    {
        return match (strtoupper((string) $method)) {
            'BANK_TRANSFER', 'VIRTUAL_ACCOUNT', 'RETAIL_OUTLET' => 'transfer',
            'EWALLET', 'OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY' => 'ewallet',
            'QR_CODE', 'QRIS' => 'qris',
            default => null,
        };
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoice(): string
    {
        return 'INV/'.date('Ymd').'/'.strtoupper(Str::random(6));
    }

    /**
     * Run a closure while holding an atomic per-user lock to prevent double submits.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws LockTimeoutException
     */
    protected function withUserLock(User $user, callable $callback)
    {
        $lockKey = 'create_pesanan_user_'.$user->id;
        $lock = Cache::lock($lockKey, 10);

        return $lock->block(5, $callback);
    }
}
