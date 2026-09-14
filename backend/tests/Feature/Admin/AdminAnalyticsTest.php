<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pendaki;
use App\Models\Pesanan;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->mitraUser = User::factory()->create(['role' => 'mitra']);
    $this->climberUser = User::factory()->create(['role' => 'pendaki']);
});

it('prevents unauthenticated or non-admin users from accessing admin analytics summary', function () {
    // Unauthenticated
    $this->getJson('/api/v1/admin/analytics/summary')
        ->assertStatus(401);

    // Non-admin (pendaki)
    Sanctum::actingAs($this->climberUser);
    $this->getJson('/api/v1/admin/analytics/summary')
        ->assertStatus(403);

    // Non-admin (mitra)
    Sanctum::actingAs($this->mitraUser);
    $this->getJson('/api/v1/admin/analytics/summary')
        ->assertStatus(403);
});

it('returns accurate action queue counts for pending kyc, withdrawals, and refunds', function () {
    // 1. Create KYC pendaki: 2 pending, 1 verified
    Pendaki::create([
        'user_id' => $this->climberUser->id,
        'nama_lengkap' => 'Pendaki 1',
        'jenis_identitas' => 'ktp',
        'nomor_identitas' => '3201123456780001',
        'foto_identitas' => 'ktp/pendaki1.jpg',
        'tanggal_lahir' => '1995-01-01',
        'jenis_kelamin' => 'l',
        'alamat' => 'Jl. Pendaki 1',
        'telepon' => '081234567891',
        'nama_kontak_darurat' => 'Darurat 1',
        'telepon_darurat' => '081234567899',
        'hubungan_darurat' => 'Keluarga',
        'status_verifikasi' => 'pending',
    ]);
    $climber2 = User::factory()->create(['role' => 'pendaki']);
    Pendaki::create([
        'user_id' => $climber2->id,
        'nama_lengkap' => 'Pendaki 2',
        'jenis_identitas' => 'ktp',
        'nomor_identitas' => '3201123456780002',
        'foto_identitas' => 'ktp/pendaki2.jpg',
        'tanggal_lahir' => '1996-02-02',
        'jenis_kelamin' => 'l',
        'alamat' => 'Jl. Pendaki 2',
        'telepon' => '081234567892',
        'nama_kontak_darurat' => 'Darurat 2',
        'telepon_darurat' => '081234567899',
        'hubungan_darurat' => 'Keluarga',
        'status_verifikasi' => 'pending',
    ]);
    $climber3 = User::factory()->create(['role' => 'pendaki']);
    Pendaki::create([
        'user_id' => $climber3->id,
        'nama_lengkap' => 'Pendaki 3',
        'jenis_identitas' => 'ktp',
        'nomor_identitas' => '3201123456780003',
        'foto_identitas' => 'ktp/pendaki3.jpg',
        'tanggal_lahir' => '1997-03-03',
        'jenis_kelamin' => 'p',
        'alamat' => 'Jl. Pendaki 3',
        'telepon' => '081234567893',
        'nama_kontak_darurat' => 'Darurat 3',
        'telepon_darurat' => '081234567899',
        'hubungan_darurat' => 'Keluarga',
        'status_verifikasi' => 'disetujui',
    ]);

    // 2. Create Mitra, Basecamp, Wallet
    $mitra = Mitra::create([
        'user_id' => $this->mitraUser->id,
        'nama_pemilik' => 'Pak Mitra',
        'telepon' => '081234567890',
        'alamat' => 'Basecamp',
        'status' => 'aktif',
        'nik' => '3201123456780004',
        'rekening_bank' => '123456',
        'nama_rekening' => 'Pak Mitra',
        'bank' => 'BCA',
    ]);
    $wallet = Wallet::create([
        'mitra_id' => $mitra->id,
        'saldo_tersedia' => 500000,
        'saldo_mengendap' => 0,
    ]);

    // 1 pending withdrawal, 1 completed
    Withdrawal::create([
        'mitra_id' => $mitra->id,
        'wallet_id' => $wallet->id,
        'nominal' => 100000,
        'biaya_admin' => 0,
        'bank' => 'BCA',
        'rekening_bank' => '123456',
        'nama_rekening' => 'Pak Mitra',
        'status' => 'pending',
    ]);
    Withdrawal::create([
        'mitra_id' => $mitra->id,
        'wallet_id' => $wallet->id,
        'nominal' => 50000,
        'biaya_admin' => 0,
        'bank' => 'BCA',
        'rekening_bank' => '123456',
        'nama_rekening' => 'Pak Mitra',
        'status' => 'completed',
    ]);

    // 3. Create Gunung, Jalur, Basecamp, Pesanan, Pembayaran, Refund: 1 pending
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Slamet',
        'deskripsi' => 'Deskripsi',
        'tinggi_mdpl' => 3428,
        'lokasi' => 'Jateng',
        'foto' => 'gunungs/slamet.jpg',
        'status' => 'aktif',
    ]);
    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Bambangan',
        'deskripsi' => 'Jalur',
        'titik_awal_mdpl' => '1500 MDPL',
        'titik_akhir_mdpl' => '3428 MDPL',
        'waktu_tempuh' => '8 Jam',
        'panjang_jalur' => '7 Km',
        'tingkat_kesulitan' => 'sedang',
        'status' => 'open',
    ]);
    $basecamp = Basecamp::create([
        'mitra_id' => $mitra->id,
        'jalur_id' => $jalur->id,
        'nama_basecamp' => 'Basecamp Bambangan',
        'longitude' => '109.123',
        'latitude' => '-7.123',
        'jam_operasional' => '07:00 - 17:00',
    ]);
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260913/TEST01',
        'user_id' => $this->climberUser->id,
        'basecamp_id' => $basecamp->id,
        'jalur_id' => $jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::now()->format('Y-m-d'),
        'total_bayar' => 105000.00,
    ]);
    $pembayaran = $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'payment_status' => 'settled',
        'payment_channel' => 'BCA_VA',
    ]);
    Refund::create([
        'pesanan_id' => $pesanan->id,
        'pembayaran_id' => $pembayaran->id,
        'mitra_id' => $mitra->id,
        'nominal' => 100000,
        'alasan' => 'Batal mendaki',
        'status' => 'pending',
    ]);

    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/v1/admin/analytics/summary');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.action_queues.kyc_pending_count', 2)
        ->assertJsonPath('data.action_queues.withdrawal_pending_count', 1)
        ->assertJsonPath('data.action_queues.refund_pending_count', 1)
        ->assertJsonPath('data.total_pending', 4);
});
