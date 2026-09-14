<?php

use App\Models\Basecamp;
use App\Models\DetailPesanan;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EscrowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Merbabu',
        'deskripsi' => 'Gunung Jawa Tengah.',
        'tinggi_mdpl' => 3142,
        'lokasi' => 'Boyolali, Jawa Tengah',
        'foto' => 'gunungs/merbabu.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Selo',
        'deskripsi' => 'Jalur pendakian via Selo.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '3142 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'open',
        'panjang_jalur' => '7.5 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->userMitra = User::factory()->create(['role' => 'mitra', 'name' => 'Mitra Selo']);
    $this->mitra = Mitra::create([
        'user_id' => $this->userMitra->id,
        'nama_pemilik' => 'Mitra Selo',
        'telepon' => '081200000001',
        'alamat' => 'Selo Boyolali',
        'nik' => '3201234567890001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Mitra Selo',
        'bank' => 'Bank BCA',
        'status' => 'aktif',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Selo Indah',
        'latitude' => '-7.412345',
        'longitude' => '110.432109',
        'jam_operasional' => '24 Jam',
    ]);

    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 0.00,
        'total_withdrawn' => 0.00,
    ]);

    $this->climber = User::factory()->create(['role' => 'pendaki', 'name' => 'Pendaki Budi']);

    $this->produk = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tenda Dome 4P',
        'deskripsi' => 'Tenda kapasitas 4 orang',
        'harga' => 50000.00,
        'kategori' => 'rental',
        'stok' => 10,
        'status' => 'aktif',
    ]);
});

test('mitra can check out an on_going order, completing it and releasing escrow to available balance', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260914/TEST01',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'on_going',
        'status_escrow' => 'holding',
        'subtotal' => 100000.00,
        'total_bayar' => 100000.00,
        'pendapatan_mitra' => 90000.00,
        'tanggal_booking' => now()->toDateString(),
    ]);

    $item = DetailPesanan::create([
        'pesanan_id' => $pesanan->id,
        'produk_id' => $this->produk->id,
        'qty' => 1,
        'harga' => 50000.00,
        'subtotal' => 50000.00,
        'status_operasional' => 'active',
    ]);

    // Initial escrow holding in wallet
    $escrowService = app(EscrowService::class);
    $escrowService->recordPaymentHolding($pesanan);

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(90000.00)
        ->and((float) $this->wallet->saldo_available)->toBe(0.00);

    Sanctum::actingAs($this->userMitra);

    $response = $this->postJson("/api/v1/mitra/orders/{$pesanan->id}/check-out");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.status_escrow', 'released');

    $pesanan->refresh();
    expect($pesanan->status)->toBe('completed')
        ->and($pesanan->status_escrow)->toBe('released');

    $item->refresh();
    expect($item->status_operasional)->toBe('completed');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(0.00)
        ->and((float) $this->wallet->saldo_available)->toBe(90000.00);
});

test('mitra cannot check out an order that is not on_going', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260914/TEST02',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'status_escrow' => 'holding',
        'subtotal' => 100000.00,
        'total_bayar' => 100000.00,
        'pendapatan_mitra' => 90000.00,
        'tanggal_booking' => now()->toDateString(),
    ]);

    Sanctum::actingAs($this->userMitra);

    $response = $this->postJson("/api/v1/mitra/orders/{$pesanan->id}/check-out");

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Hanya pesanan berstatus on_going (sedang aktif mendaki) yang dapat di-check out / diselesaikan.');
});

test('mitra cannot check out an order belonging to another basecamp/mitra', function () {
    $otherMitraUser = User::factory()->create(['role' => 'mitra', 'name' => 'Mitra Lain']);

    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260914/TEST03',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'on_going',
        'status_escrow' => 'holding',
        'subtotal' => 100000.00,
        'total_bayar' => 100000.00,
        'pendapatan_mitra' => 90000.00,
        'tanggal_booking' => now()->toDateString(),
    ]);

    Sanctum::actingAs($otherMitraUser);

    $response = $this->postJson("/api/v1/mitra/orders/{$pesanan->id}/check-out");

    $response->assertStatus(403);
});
