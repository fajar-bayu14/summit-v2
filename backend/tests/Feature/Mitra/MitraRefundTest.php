<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->userClimber = User::factory()->create(['role' => 'pendaki']);
    $this->userMitra = User::factory()->create(['role' => 'mitra']);
    $this->otherUserMitra = User::factory()->create(['role' => 'mitra']);

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

    $this->otherMitra = Mitra::create([
        'user_id' => $this->otherUserMitra->id,
        'nama_pemilik' => 'Siti Aminah',
        'telepon' => '081298765432',
        'alamat' => 'Basecamp Selabintana',
        'status' => 'aktif',
        'nik' => '3201234567890002',
        'rekening_bank' => '9876543210',
        'nama_rekening' => 'Siti Aminah',
        'bank' => 'Mandiri',
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

    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 180000.00,
        'saldo_available' => 500000.00,
        'total_withdrawn' => 0.00,
    ]);
});

test('climber calculates 100% refund on H-3 and 50% on H-1', function () {
    // 1. Pesanan H-3
    $pesananH3 = Pesanan::create([
        'invoice' => 'INV/20260903/H3',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::today()->addDays(3)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);
    $pesananH3->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $resH3 = $this->actingAs($this->userClimber)
        ->postJson(route('refund.store', ['invoice' => $pesananH3->invoice]), [
            'alasan' => 'Ada acara mendadak',
            'bank_tujuan' => 'BCA',
            'rekening_tujuan' => '1234567890',
            'nama_tujuan' => 'Pendaki H3',
        ]);

    $resH3->assertStatus(201)
        ->assertJsonPath('data.nominal', 105000)
        ->assertJsonPath('data.refund_category', 'pre_trip')
        ->assertJsonPath('data.mitra_id', $this->mitra->id);

    // 2. Pesanan H-1
    $pesananH1 = Pesanan::create([
        'invoice' => 'INV/20260903/H1',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::today()->addDays(1)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);
    $pesananH1->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $resH1 = $this->actingAs($this->userClimber)
        ->postJson(route('refund.store', ['invoice' => $pesananH1->invoice]), [
            'alasan' => 'Batal mendadak',
            'bank_tujuan' => 'BCA',
            'rekening_tujuan' => '1234567890',
            'nama_tujuan' => 'Pendaki H1',
        ]);

    $resH1->assertStatus(201)
        ->assertJsonPath('data.nominal', 52500) // 50% of 105000
        ->assertJsonPath('data.refund_category', 'pre_trip');
});

test('mitra can view and list only their own refunds', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/MTR01',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::today()->addDays(4)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $pembayaran = $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    Refund::create([
        'pesanan_id' => $pesanan->id,
        'pembayaran_id' => $pembayaran->id,
        'user_id' => $this->userClimber->id,
        'mitra_id' => $this->mitra->id,
        'nominal' => 105000.00,
        'alasan' => 'Membatalkan perjalanan',
        'status' => 'pending',
        'refund_category' => 'pre_trip',
        'bank_tujuan' => 'BCA',
        'rekening_tujuan' => '1234567890',
        'nama_tujuan' => 'Pendaki Satu',
    ]);

    // Mitra 1 sees 1 refund
    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.refunds.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // Mitra 2 sees 0 refunds
    $this->actingAs($this->otherUserMitra)
        ->getJson(route('mitra.refunds.index'))
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.data');
});

test('mitra can approve refund directly deducting from escrow pending balance', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/MTR02',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::today()->addDays(4)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $pembayaran = $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $refund = Refund::create([
        'pesanan_id' => $pesanan->id,
        'pembayaran_id' => $pembayaran->id,
        'user_id' => $this->userClimber->id,
        'mitra_id' => $this->mitra->id,
        'nominal' => 105000.00,
        'alasan' => 'Membatalkan booking',
        'status' => 'pending',
        'refund_category' => 'pre_trip',
        'bank_tujuan' => 'BCA',
        'rekening_tujuan' => '1234567890',
        'nama_tujuan' => 'Pendaki Dua',
    ]);

    $this->wallet->update(['saldo_pending' => 90000.00]);

    $response = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.refunds.approve', ['id' => $refund->id]));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'approved_by_mitra');

    $refund->refresh();
    expect($refund->status)->toBe('approved_by_mitra');
    expect($refund->mitra_reviewed_at)->not->toBeNull();

    $pesanan->refresh();
    expect($pesanan->status)->toBe('refunded');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(0.00); // deducted from pending
});

test('mitra can reject refund with reason and climber can dispute to admin', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/MTR03',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::today()->addDays(4)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $pembayaran = $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $refund = Refund::create([
        'pesanan_id' => $pesanan->id,
        'pembayaran_id' => $pembayaran->id,
        'user_id' => $this->userClimber->id,
        'mitra_id' => $this->mitra->id,
        'nominal' => 105000.00,
        'alasan' => 'Membatalkan sewa alat',
        'status' => 'pending',
        'refund_category' => 'pre_trip',
        'bank_tujuan' => 'BCA',
        'rekening_tujuan' => '1234567890',
        'nama_tujuan' => 'Pendaki Tiga',
    ]);

    // 1. Mitra rejects refund
    $rejectRes = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.refunds.reject', ['id' => $refund->id]), [
            'alasan_penolakan' => 'Barang sudah disiapkan dan di-pack khusus.',
        ]);

    $rejectRes->assertStatus(200)
        ->assertJsonPath('data.status', 'rejected_by_mitra')
        ->assertJsonPath('data.mitra_alasan_penolakan', 'Barang sudah disiapkan dan di-pack khusus.');

    // 2. Climber disputes rejection to Admin
    $disputeRes = $this->actingAs($this->userClimber)
        ->postJson(route('refund.dispute', ['id' => $refund->id]), [
            'alasan_dispute' => 'Saya membatalkan H-4 sebelum barang di-pack dan konfirmasi CS dibatalkan.',
        ]);

    $disputeRes->assertStatus(200)
        ->assertJsonPath('data.status', 'disputed')
        ->assertJsonPath('data.is_disputed', true)
        ->assertJsonPath('data.dispute_reason', 'Saya membatalkan H-4 sebelum barang di-pack dan konfirmasi CS dibatalkan.');
});
