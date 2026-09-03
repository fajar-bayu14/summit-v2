<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\ProdukTiket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\Invoice;
use Xendit\Invoice\InvoiceApi;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'pendaki']);

    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air terjun dan air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $userMitra = User::factory()->create(['role' => 'mitra', 'name' => 'Budi Santoso']);
    $this->mitra = Mitra::create([
        'user_id' => $userMitra->id,
        'nama_pemilik' => 'Budi Santoso',
        'telepon' => '081234567890',
        'alamat' => 'Jl. Raya Summit No. 10',
        'status' => 'aktif',
        'nik' => '3201234567890001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'bank' => 'Bank BCA',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Cibodas Indah',
        'latitude' => '-6.793214',
        'longitude' => '107.001234',
        'jam_operasional' => '24 Jam',
    ]);

    $this->produkTicket = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tiket Simaksi Cibodas',
        'kategori' => 'ticket',
        'harga' => 20000.00,
        'is_active' => true,
    ]);

    $this->produkTiketDetail = ProdukTiket::create([
        'produk_id' => $this->produkTicket->id,
        'jalur_id' => $this->jalur->id,
        'jam_buka' => '07:00:00',
        'jam_tutup' => '17:00:00',
    ]);

    $this->produkRental = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Sewa Tenda',
        'kategori' => 'rental',
        'harga' => 35000.00,
        'stok' => 10,
        'is_active' => true,
    ]);
});

function mockXenditInvoice(?string $externalId = null, ?string $status = 'PENDING'): void
{
    $invoice = new Invoice([
        'id' => 'inv_abc123xyz',
        'external_id' => $externalId ?? 'INV/20260808/TEST',
        'status' => $status,
        'invoice_url' => 'https://checkout.xendit.co/v2/invoice/inv_abc123xyz',
        'expiry_date' => new DateTime('+1 day'),
        'amount' => 95000,
    ]);

    $api = Mockery::mock(InvoiceApi::class);
    $api->shouldReceive('setApiKey')->andReturnSelf();
    $api->shouldReceive('createInvoice')
        ->withArgs(function ($request) {
            expect($request)->toBeInstanceOf(CreateInvoiceRequest::class)
                ->and($request->getPaymentMethods())->toBeNull();

            return true;
        })
        ->andReturn($invoice);

    app()->instance(InvoiceApi::class, $api);
}

function createPendingPesanan(array $overrides = []): Pesanan
{
    $data = array_merge([
        'invoice' => 'INV/20260808/TEST',
        'user_id' => test()->user->id,
        'basecamp_id' => test()->basecamp->id,
        'jalur_id' => test()->jalur->id,
        'status' => 'pending',
        'subtotal' => 35000.00,
        'tanggal_booking' => Carbon::now()->addDays(5)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 3500.00,
        'pendapatan_mitra' => 31500.00,
        'total_bayar' => 40000.00,
    ], $overrides);

    $pesanan = Pesanan::create($data);

    $pesanan->details()->create([
        'produk_id' => test()->produkRental->id,
        'qty' => 1,
        'harga' => 35000.00,
        'subtotal' => 35000.00,
        'status_operasional' => 'pending',
    ]);

    $pesanan->pembayaran()->create([
        'amount' => 40000.00,
        'status' => 'pending',
    ]);

    return $pesanan;
}

test('climber can create the cart booking context', function () {
    $bookingDate = Carbon::now()->addDays(5)->format('Y-m-d');
    $response = $this->actingAs($this->user)->postJson(route('cart.store'), [
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
        'tanggal_selesai_booking' => Carbon::now()->addDays(7)->format('Y-m-d'),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['status', 'message', 'data' => ['id', 'basecamp_id', 'jalur_id', 'tanggal_booking']]);

    $this->assertDatabaseHas('carts', [
        'user_id' => $this->user->id,
        'basecamp_id' => $this->basecamp->id,
        'status' => 'active',
    ]);
});

test('climber can add and merge items into the active cart', function () {
    $bookingDate = Carbon::now()->addDays(5)->format('Y-m-d');
    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 2,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $this->assertDatabaseHas('cart_items', [
        'produk_id' => $this->produkRental->id,
        'qty' => 2,
    ]);

    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 3,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $this->assertDatabaseHas('cart_items', [
        'produk_id' => $this->produkRental->id,
        'qty' => 5,
    ]);
});

test('adding a product from a different basecamp to the active cart is rejected', function () {
    $bookingDate = Carbon::now()->addDays(5)->format('Y-m-d');
    $otherJalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Gunung Putri',
        'deskripsi' => 'Jalur kedua.',
        'titik_awal_mdpl' => '1200 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '8 Jam',
        'status' => 'open',
        'panjang_jalur' => '11 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    $otherBasecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $otherJalur->id,
        'nama_basecamp' => 'Basecamp Gunung Putri',
        'latitude' => '-6.78',
        'longitude' => '107.01',
        'jam_operasional' => '24 Jam',
    ]);

    $otherProduct = Produk::create([
        'basecamp_id' => $otherBasecamp->id,
        'nama_produk' => 'Tiket Simaksi Gunung Putri',
        'kategori' => 'ticket',
        'harga' => 25000.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 1,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $otherProduct->id,
        'qty' => 1,
        'jalur_id' => $otherJalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(422)->assertJsonValidationErrors('produk_id');
});

test('climber can update and remove a cart item, and clear the cart', function () {
    $bookingDate = Carbon::now()->addDays(5)->format('Y-m-d');
    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 2,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $itemId = $this->user->carts()->first()->items()->first()->id;

    $this->actingAs($this->user)->patchJson(route('cart.update-item', ['itemId' => $itemId]), [
        'qty' => 4,
    ])->assertStatus(200);

    $this->assertDatabaseHas('cart_items', ['id' => $itemId, 'qty' => 4]);

    $this->actingAs($this->user)->deleteJson(route('cart.destroy-item', ['itemId' => $itemId]))
        ->assertStatus(200);

    $this->assertDatabaseMissing('cart_items', ['id' => $itemId]);

    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 1,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $this->actingAs($this->user)->deleteJson(route('cart.destroy'))->assertStatus(200);

    $this->assertDatabaseMissing('carts', ['user_id' => $this->user->id, 'status' => 'active']);
});

test('checkout creates an order from the cart and generates a Xendit invoice', function () {
    $bookingDate = Carbon::now()->addDays(5)->format('Y-m-d');
    mockXenditInvoice();

    $this->actingAs($this->user)->postJson(route('cart.add-item'), [
        'produk_id' => $this->produkRental->id,
        'qty' => 1,
        'jalur_id' => $this->jalur->id,
        'tanggal_booking' => $bookingDate,
    ])->assertStatus(201);

    $response = $this->actingAs($this->user)->postJson(route('pesanan.checkout'), [
        'anggotas' => [
            ['nama_anggota' => 'Hiker Satu', 'nik_identitas' => '3201234567890005'],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('checkout_url', 'https://checkout.xendit.co/v2/invoice/inv_abc123xyz')
        ->assertJsonStructure([
            'status',
            'message',
            'checkout_url',
            'data' => ['id', 'invoice', 'status', 'pembayaran'],
        ]);

    $pesanan = Pesanan::where('user_id', $this->user->id)->first();

    expect($pesanan)->not->toBeNull();
    expect($pesanan->status)->toBe('pending');
    expect($pesanan->subtotal)->toBe('35000.00');
    expect($pesanan->total_bayar)->toBe('40000.00');
    expect($pesanan->tanggal_selesai_booking)->toBeNull();

    $pembayaran = $pesanan->pembayaran;
    expect($pembayaran->reference_id)->toBe('inv_abc123xyz');
    expect($pembayaran->checkout_url)->toBe('https://checkout.xendit.co/v2/invoice/inv_abc123xyz');
    expect($pembayaran->status)->toBe('pending');
    expect($pembayaran->expired_at)->not->toBeNull();

    $this->assertDatabaseHas('carts', [
        'user_id' => $this->user->id,
        'status' => 'checked_out',
    ]);

    // Stock is held while payment is pending
    $this->produkRental->refresh();
    expect($this->produkRental->stok)->toBe(9);
});

test('checkout fails when the active cart is empty', function () {
    $response = $this->actingAs($this->user)->postJson(route('pesanan.checkout'), [
        'anggotas' => [
            ['nama_anggota' => 'Hiker Satu', 'nik_identitas' => '3201234567890005'],
        ],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('cart');
});

test('webhook marks the order as paid and is idempotent', function () {
    $pesanan = createPendingPesanan();

    $payload = [
        'id' => 'inv_abc123xyz',
        'external_id' => $pesanan->invoice,
        'status' => 'PAID',
        'amount' => 40000,
        'paid_amount' => 40000,
        'payment_channel' => 'QRIS',
        'payment_method' => 'QR_CODE',
    ];

    $this->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200)->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('pesanans', ['id' => $pesanan->id, 'status' => 'paid']);
    $this->assertDatabaseHas('pembayarans', [
        'pesanan_id' => $pesanan->id,
        'status' => 'success',
        'reference_id' => 'inv_abc123xyz',
        'paid_amount' => 40000.00,
    ]);

    // Idempotent: a second PAID callback leaves the state unchanged
    $this->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200);

    $this->assertSame(1, Pembayaran::where('pesanan_id', $pesanan->id)->count());

    $this->assertDatabaseHas('payment_webhook_logs', [
        'pesanan_id' => $pesanan->id,
        'event' => 'invoice.paid',
        'is_valid' => true,
    ]);
});

test('webhook for an unknown invoice returns 404', function () {
    $this->postJson(route('xendit.webhook'), [
        'external_id' => 'INV/UNKNOWN/001',
        'status' => 'PAID',
    ])->assertStatus(404);
});

test('webhook EXPIRED marks the order as expired and restores stock', function () {
    $this->produkRental->decrement('stok', 1);
    $pesanan = createPendingPesanan();

    $this->postJson(route('xendit.webhook'), [
        'external_id' => $pesanan->invoice,
        'status' => 'EXPIRED',
    ])->assertStatus(200);

    $this->assertDatabaseHas('pesanans', ['id' => $pesanan->id, 'status' => 'expired']);
    $this->assertDatabaseHas('pembayarans', ['pesanan_id' => $pesanan->id, 'status' => 'expired']);

    $this->produkRental->refresh();
    expect($this->produkRental->stok)->toBe(10);
});

test('climber can cancel their own pending order and stock is restored', function () {
    $this->produkRental->decrement('stok', 1);
    $pesanan = createPendingPesanan();

    $this->actingAs($this->user)->postJson(route('pesanan.cancel', ['invoice' => $pesanan->invoice]))
        ->assertStatus(200)->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('pesanans', ['id' => $pesanan->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('pembayarans', ['pesanan_id' => $pesanan->id, 'status' => 'cancelled']);

    $this->produkRental->refresh();
    expect($this->produkRental->stok)->toBe(10);
});

test('a paid order cannot be cancelled', function () {
    $pesanan = createPendingPesanan(['status' => 'paid']);

    $this->actingAs($this->user)->postJson(route('pesanan.cancel', ['invoice' => $pesanan->invoice]))
        ->assertStatus(422)->assertJsonValidationErrors('order');
});

test('a climber cannot cancel another user order', function () {
    $pesanan = createPendingPesanan();

    $otherUser = User::factory()->create(['role' => 'pendaki']);

    $this->actingAs($otherUser)->postJson(route('pesanan.cancel', ['invoice' => $pesanan->invoice]))
        ->assertStatus(403);
});

test('orders can be listed and filtered by status for the climber', function () {
    createPendingPesanan(['invoice' => 'INV/20260808/AAA']);
    createPendingPesanan(['invoice' => 'INV/20260808/BBB', 'status' => 'paid']);

    $this->actingAs($this->user)->getJson(route('pesanan.index'))
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonCount(2, 'data');

    $this->actingAs($this->user)->getJson(route('pesanan.index', ['status' => 'paid']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'paid');
});

test('climber can view a single order detail by invoice number', function () {
    $pesanan = createPendingPesanan();

    $this->actingAs($this->user)->getJson(route('pesanan.show', ['invoice' => $pesanan->invoice]))
        ->assertStatus(200)
        ->assertJsonPath('data.invoice', $pesanan->invoice)
        ->assertJsonStructure(['status', 'data' => ['id', 'invoice', 'status', 'details', 'pembayaran']]);
});

test('orders:expire-pending command expires stale orders and restores stock', function () {
    $this->produkRental->decrement('stok', 1);
    $pesanan = createPendingPesanan();
    $pesanan->pembayaran->update(['expired_at' => now()->subMinute()]);

    $this->artisan('orders:expire-pending')
        ->expectsOutput('Expired 1 pending order(s).')
        ->assertSuccessful();

    $this->assertDatabaseHas('pesanans', ['id' => $pesanan->id, 'status' => 'expired']);

    $this->produkRental->refresh();
    expect($this->produkRental->stok)->toBe(10);
});
