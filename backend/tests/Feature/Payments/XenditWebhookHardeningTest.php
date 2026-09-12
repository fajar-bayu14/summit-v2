<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\KuotaHarianTiket;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->validToken = config('services.xendit.callback_token');

    $this->user = User::factory()->create(['role' => 'pendaki']);

    $this->mitraUser = User::factory()->create(['role' => 'mitra']);
    $this->mitra = Mitra::create([
        'user_id' => $this->mitraUser->id,
        'nama_pemilik' => 'Budi Pengelola',
        'telepon' => '08123456789',
        'alamat' => 'Desa Sembalun Lawang',
        'status' => 'aktif',
        'nik' => '5201234567890001',
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Pengelola',
    ]);

    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Rinjani',
        'deskripsi' => 'Gunung indah di NTB.',
        'tinggi_mdpl' => 3726,
        'lokasi' => 'Lombok, NTB',
        'foto' => 'rinjani.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Via Sembalun',
        'deskripsi' => 'Jalur favorit.',
        'titik_awal_mdpl' => '1156 MDPL',
        'titik_akhir_mdpl' => '3726 MDPL',
        'waktu_tempuh' => '8 Jam',
        'status' => 'open',
        'panjang_jalur' => '10 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Sembalun',
        'latitude' => '-8.35',
        'longitude' => '116.52',
        'jam_operasional' => '24 Jam',
    ]);

    $this->produk = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tiket Masuk Rinjani',
        'kategori' => 'ticket',
        'harga' => 50000.00,
        'is_active' => true,
    ]);

    $this->produkTiket = $this->produk->tiket()->create([
        'jalur_id' => $this->jalur->id,
        'jam_buka' => '07:00:00',
        'jam_tutup' => '17:00:00',
    ]);
});

function createTestPesanan($user, $basecamp, $jalur, $produk, $produkTiket, array $overrides = []): Pesanan
{
    $bookingDate = Carbon::now()->addDays(5)->startOfDay();

    $pesanan = Pesanan::create(array_merge([
        'invoice' => 'INV/'.date('Ymd').'/'.strtoupper(Str::random(6)),
        'user_id' => $user->id,
        'basecamp_id' => $basecamp->id,
        'jalur_id' => $jalur->id,
        'status' => 'pending',
        'subtotal' => 50000.00,
        'tanggal_booking' => $bookingDate->toDateString(),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 5000.00,
        'pendapatan_mitra' => 45000.00,
        'total_bayar' => 55000.00,
        'status_escrow' => 'holding',
    ], $overrides));

    $pesanan->details()->create([
        'produk_id' => $produk->id,
        'qty' => 1,
        'harga' => 50000.00,
        'subtotal' => 50000.00,
        'status_operasional' => 'pending',
        'kode_tiket' => 'TKT-'.Str::random(8),
    ]);

    $pesanan->pembayaran()->create([
        'amount' => 55000.00,
        'status' => 'pending',
        'expired_at' => now()->addHour(),
    ]);

    KuotaHarianTiket::create([
        'produk_tiket_id' => $produkTiket->id,
        'tanggal' => $bookingDate,
        'kuota_total' => 50,
        'kuota_tersisa' => 50,
    ]);

    return $pesanan;
}

test('payment webhook without x-callback-token is rejected with 401', function () {
    $this->postJson(route('xendit.webhook'), [
        'id' => 'inv_test_fake',
        'external_id' => 'INV/20260911/FAKE',
        'status' => 'PAID',
    ])->assertStatus(401)
        ->assertJsonPath('error_code', 'ERR_UNAUTHORIZED_WEBHOOK');
});

test('payment webhook with invalid x-callback-token is rejected with 401', function () {
    $this->withHeader('x-callback-token', 'completely-wrong-token')
        ->postJson(route('xendit.webhook'), [
            'id' => 'inv_test_fake',
            'external_id' => 'INV/20260911/FAKE',
            'status' => 'PAID',
        ])->assertStatus(401)
        ->assertJsonPath('error_code', 'ERR_INVALID_WEBHOOK_TOKEN');
});

test('disbursement webhook without valid token is rejected with 401', function () {
    $this->postJson(route('xendit.disbursement.webhook'), [
        'id' => 'disb_fake',
        'status' => 'COMPLETED',
    ])->assertStatus(401);

    $this->withHeader('x-callback-token', 'invalid-token')
        ->postJson(route('xendit.disbursement.webhook'), [
            'id' => 'disb_fake',
            'status' => 'COMPLETED',
        ])->assertStatus(401);
});

test('webhook paid delivery is strictly idempotent and prevents double escrow credit', function () {
    $pesanan = createTestPesanan($this->user, $this->basecamp, $this->jalur, $this->produk, $this->produkTiket);

    $payload = [
        'id' => 'inv_xendit_123',
        'external_id' => $pesanan->invoice,
        'status' => 'PAID',
        'amount' => 55000,
        'paid_amount' => 55000,
        'payment_channel' => 'QRIS',
        'payment_method' => 'QR_CODE',
    ];

    // First delivery
    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200);

    $pesanan->refresh();
    expect($pesanan->status)->toBe('paid');
    expect($pesanan->pembayaran->status)->toBe('success');

    $wallet = Wallet::where('mitra_id', $this->mitra->id)->first();
    expect((float) $wallet->saldo_pending)->toBe(45000.00);

    // Assert exactly 1 holding transaction was recorded
    $holdingCount = WalletTransaction::where('pesanan_id', $pesanan->id)
        ->where('type', 'inflow_holding')
        ->count();
    expect($holdingCount)->toBe(1);

    // Second delivery (Redelivery simulation)
    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200);

    $wallet->refresh();
    // Balance MUST NOT increase twice
    expect((float) $wallet->saldo_pending)->toBe(45000.00);

    // Transactions count MUST remain 1
    $holdingCountAfter = WalletTransaction::where('pesanan_id', $pesanan->id)
        ->where('type', 'inflow_holding')
        ->count();
    expect($holdingCountAfter)->toBe(1);
});

test('late payment recovers and reopens expired order when quota is available', function () {
    $pesanan = createTestPesanan($this->user, $this->basecamp, $this->jalur, $this->produk, $this->produkTiket, [
        'status' => 'expired',
    ]);
    $pesanan->pembayaran->update(['status' => 'expired']);

    $payload = [
        'id' => 'inv_late_001',
        'external_id' => $pesanan->invoice,
        'status' => 'PAID',
        'amount' => 55000,
        'paid_amount' => 55000,
        'payment_channel' => 'BCA',
        'payment_method' => 'VIRTUAL_ACCOUNT',
    ];

    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200);

    $pesanan->refresh();
    expect($pesanan->status)->toBe('paid');
    expect($pesanan->pembayaran->status)->toBe('success');

    // Quota was re-deducted
    $kuota = KuotaHarianTiket::where('produk_tiket_id', $this->produkTiket->id)->first();
    expect($kuota->kuota_tersisa)->toBe(49); // 50 - 1

    // Escrow holding was credited
    $wallet = Wallet::where('mitra_id', $this->mitra->id)->first();
    expect((float) $wallet->saldo_pending)->toBe(45000.00);
});

test('late payment retains order as expired when quota is sold out and does not credit partner escrow', function () {
    $pesanan = createTestPesanan($this->user, $this->basecamp, $this->jalur, $this->produk, $this->produkTiket, [
        'status' => 'expired',
    ]);
    $pesanan->pembayaran->update(['status' => 'expired']);

    // Simulate quota completely exhausted
    KuotaHarianTiket::where('produk_tiket_id', $this->produkTiket->id)
        ->update(['kuota_tersisa' => 0]);

    $payload = [
        'id' => 'inv_late_soldout',
        'external_id' => $pesanan->invoice,
        'status' => 'PAID',
        'amount' => 55000,
        'paid_amount' => 55000,
        'payment_channel' => 'BCA',
        'payment_method' => 'VIRTUAL_ACCOUNT',
    ];

    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.webhook'), $payload)
        ->assertStatus(200);

    $pesanan->refresh();
    // Order MUST stay expired to avoid overbooking
    expect($pesanan->status)->toBe('expired');
    // Payment recorded as received in platform suspense
    expect($pesanan->pembayaran->status)->toBe('success');

    // Quota remains 0
    $kuota = KuotaHarianTiket::where('produk_tiket_id', $this->produkTiket->id)->first();
    expect($kuota->kuota_tersisa)->toBe(0);

    // Escrow holding MUST NOT be given to partner
    $wallet = Wallet::where('mitra_id', $this->mitra->id)->first();
    expect($wallet)->toBeNull();
});

test('disbursement webhook REVERSED successfully rolls back completed withdrawal balance', function () {
    $wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 50000.00,
        'total_withdrawn' => 100000.00,
    ]);

    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $wallet->id,
        'disbursement_id' => 'disb_rev_001',
        'nominal' => 100000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Pengelola',
        'status' => 'completed',
        'completed_at' => now()->subHour(),
    ]);

    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.disbursement.webhook'), [
            'id' => 'disb_rev_001',
            'external_id' => 'WD-'.$withdrawal->id,
            'status' => 'REVERSED',
            'failure_code' => 'ACCOUNT_CLOSED',
        ])->assertStatus(200);

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('reversed');

    $wallet->refresh();
    // total_withdrawn reduced: 100k - 100k = 0
    expect((float) $wallet->total_withdrawn)->toBe(0.00);
    // saldo_available restored: 50k + 100k = 150k
    expect((float) $wallet->saldo_available)->toBe(150000.00);

    $this->assertDatabaseHas('wallet_transactions', [
        'wallet_id' => $wallet->id,
        'withdrawal_id' => $withdrawal->id,
        'type' => 'disbursement_reversed',
        'nominal' => 100000.00,
    ]);

    // Redelivered REVERSED is idempotent
    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.disbursement.webhook'), [
            'id' => 'disb_rev_001',
            'external_id' => 'WD-'.$withdrawal->id,
            'status' => 'REVERSED',
        ])->assertStatus(200);

    $wallet->refresh();
    expect((float) $wallet->saldo_available)->toBe(150000.00);
});

test('disbursement FAILED webhook redelivery does not double refund available balance', function () {
    $wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 10000.00,
        'total_withdrawn' => 0.00,
    ]);

    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $wallet->id,
        'disbursement_id' => 'disb_fail_002',
        'nominal' => 50000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Pengelola',
        'status' => 'processing',
    ]);

    $payload = [
        'id' => 'disb_fail_002',
        'external_id' => 'WD-'.$withdrawal->id,
        'status' => 'FAILED',
        'failure_code' => 'INVALID_DESTINATION',
    ];

    // First delivery
    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.disbursement.webhook'), $payload)
        ->assertStatus(200);

    $wallet->refresh();
    expect((float) $wallet->saldo_available)->toBe(60000.00); // 10k + 50k

    // Second delivery (Redelivery)
    $this->withHeader('x-callback-token', $this->validToken)
        ->postJson(route('xendit.disbursement.webhook'), $payload)
        ->assertStatus(200);

    $wallet->refresh();
    // MUST NOT refund another 50k!
    expect((float) $wallet->saldo_available)->toBe(60000.00);
});
