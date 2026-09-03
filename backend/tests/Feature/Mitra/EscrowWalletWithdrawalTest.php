<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\EscrowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->userClimber = User::factory()->create(['role' => 'pendaki']);
    $this->userMitra = User::factory()->create(['role' => 'mitra']);

    $this->mitra = Mitra::create([
        'user_id' => $this->userMitra->id,
        'nama_pemilik' => 'Budi Santoso',
        'telepon' => '081234567890',
        'alamat' => 'Basecamp Cibodas',
        'status' => 'aktif',
        'nik' => '3201234567890001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'bank' => 'BCA',
    ]);

    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur',
        'foto' => 'gunung.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Deskripsi jalur.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Cibodas Indah',
        'latitude' => '-6.79',
        'longitude' => '107.00',
        'jam_operasional' => '24 Jam',
    ]);

    $this->produk = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Sewa Tenda Dome',
        'kategori' => 'rental',
        'harga' => 100000.00,
        'stok' => 10,
        'is_active' => true,
    ]);
});

test('webhook paid incoming credits escrow pending balance in wallet', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/TEST01',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'pending',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'pending',
    ]);

    $payload = [
        'id' => 'inv_test_123',
        'external_id' => $pesanan->invoice,
        'status' => 'PAID',
        'amount' => 105000,
        'paid_amount' => 105000,
        'payment_channel' => 'QRIS',
        'payment_method' => 'QR_CODE',
    ];

    $this->postJson(route('xendit.webhook'), $payload)->assertStatus(200);

    // Assert Wallet has 90,000 pending balance
    $wallet = Wallet::where('mitra_id', $this->mitra->id)->first();
    expect($wallet)->not->toBeNull();
    expect((float) $wallet->saldo_pending)->toBe(90000.00);
    expect((float) $wallet->saldo_available)->toBe(0.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet->id,
        'pesanan_id' => $pesanan->id,
        'type' => 'inflow_holding',
        'nominal' => 90000.00,
    ]);
});

test('completing order releases escrow balance to available', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/TEST02',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $escrowService = app(EscrowService::class);
    $escrowService->recordPaymentHolding($pesanan);

    // Release to available
    $escrowService->releaseEscrowToAvailable($pesanan);

    $wallet = Wallet::where('mitra_id', $this->mitra->id)->first();
    expect((float) $wallet->saldo_pending)->toBe(0.00);
    expect((float) $wallet->saldo_available)->toBe(90000.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'release_to_available',
        'nominal' => 90000.00,
    ]);
});

test('mitra can view wallet summary and request withdrawal', function () {
    $wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 50000.00,
        'saldo_available' => 100000.00,
        'total_withdrawn' => 0.00,
    ]);

    // View summary
    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.wallet.summary'))
        ->assertStatus(200)
        ->assertJsonPath('data.saldo_available', fn ($val) => (float) $val === 100000.0)
        ->assertJsonPath('data.total_terhitung', fn ($val) => (float) $val === 150000.0);

    // Request withdrawal
    $response = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.withdrawals.store'), [
            'nominal' => 60000.00,
            'catatan' => 'Tarik saldo mingguan',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.nominal', fn ($val) => (float) $val === 60000.0)
        ->assertJsonPath('data.status', 'pending');

    $wallet->refresh();
    expect((float) $wallet->saldo_available)->toBe(40000.00); // 100,000 - 60,000 locked

    // Cannot withdraw more than available balance
    $this->actingAs($this->userMitra)
        ->postJson(route('mitra.withdrawals.store'), [
            'nominal' => 50000.00, // available only 40,000
        ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERR_INSUFFICIENT_AVAILABLE_BALANCE');

    // Cannot withdraw less than min 50,000
    $this->actingAs($this->userMitra)
        ->postJson(route('mitra.withdrawals.store'), [
            'nominal' => 20000.00,
        ])
        ->assertStatus(422);
});

test('xendit disbursement webhook settles completed payout', function () {
    $wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 0.00,
        'total_withdrawn' => 0.00,
    ]);

    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $wallet->id,
        'disbursement_id' => 'disb_test_xyz123',
        'nominal' => 100000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'status' => 'processing',
    ]);

    $this->postJson(route('xendit.disbursement.webhook'), [
        'id' => 'disb_test_xyz123',
        'external_id' => 'WD-'.$withdrawal->id,
        'status' => 'COMPLETED',
        'amount' => 100000,
    ])->assertStatus(200);

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('completed');
    expect($withdrawal->completed_at)->not->toBeNull();

    $wallet->refresh();
    expect((float) $wallet->total_withdrawn)->toBe(100000.00);
});

test('xendit disbursement webhook failure reverts locked funds back to available', function () {
    $wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 20000.00, // after 80,000 was locked from 100,000
        'total_withdrawn' => 0.00,
    ]);

    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $wallet->id,
        'disbursement_id' => 'disb_failed_123',
        'nominal' => 80000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'status' => 'processing',
    ]);

    $this->postJson(route('xendit.disbursement.webhook'), [
        'id' => 'disb_failed_123',
        'external_id' => 'WD-'.$withdrawal->id,
        'status' => 'FAILED',
        'failure_code' => 'INVALID_ACCOUNT_NUMBER',
    ])->assertStatus(200);

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('failed');
    expect($withdrawal->failure_reason)->toBe('INVALID_ACCOUNT_NUMBER');

    $wallet->refresh();
    expect((float) $wallet->saldo_available)->toBe(100000.00); // Reverted back: 20k + 80k
});
