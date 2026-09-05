<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
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

    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 500000.00,
        'total_withdrawn' => 0.00,
    ]);
});

test('admin can list all withdrawals and filter by status', function () {
    Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $this->wallet->id,
        'nominal' => 100000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.withdrawals.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    $this->actingAs($this->admin)
        ->getJson(route('admin.withdrawals.index', ['status' => 'completed']))
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.data');
});

test('admin can approve a withdrawal request', function () {
    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $this->wallet->id,
        'nominal' => 150000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.withdrawals.approve', ['id' => $withdrawal->id]));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');

    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('processing');
    expect($withdrawal->approved_by)->toBe($this->admin->id);
    expect($withdrawal->disbursement_id)->not->toBeNull();
});

test('admin can reject a withdrawal request and balance is returned to mitra', function () {
    // 200,000 locked from wallet
    $this->wallet->update(['saldo_available' => 300000.00]);

    $withdrawal = Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $this->wallet->id,
        'nominal' => 200000.00,
        'bank' => 'BCA',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Santoso',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.withdrawals.reject', ['id' => $withdrawal->id]), [
            'alasan_penolakan' => 'Nama pemilik rekening bank tidak sesuai.',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.alasan_penolakan', 'Nama pemilik rekening bank tidak sesuai.');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_available)->toBe(500000.00); // 300k + 200k returned
});

test('climber can request refund and admin can process it', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/REF01',
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

    $pesanan->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $this->wallet->update(['saldo_pending' => 90000.00]);

    // 1. Climber submits refund request
    $refundRes = $this->actingAs($this->userClimber)
        ->postJson(route('refund.store', ['invoice' => $pesanan->invoice]), [
            'alasan' => 'Jalur Cibodas ditutup karena badai.',
            'bank_tujuan' => 'BCA',
            'rekening_tujuan' => '0987654321',
            'nama_tujuan' => 'Pendaki Satu',
        ]);

    $refundRes->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $refundId = $refundRes->json('data.id');

    // 2. Admin views refund list
    $this->actingAs($this->admin)
        ->getJson(route('admin.refunds.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // 3. Admin processes refund
    $processRes = $this->actingAs($this->admin)
        ->postJson(route('admin.refunds.process', ['id' => $refundId]), [
            'status' => 'success',
            'tipe' => 'manual',
            'bukti_transfer' => 'proofs/refund_01.jpg',
            'catatan' => 'Dana refund telah dikirim ke rekening pendaki',
        ]);

    $processRes->assertStatus(200)
        ->assertJsonPath('data.status', 'success');

    $pesanan->refresh();
    expect($pesanan->status)->toBe('refunded');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(0.00); // 90,000 deducted from holding
});

test('admin can view consolidated escrow ledger', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.escrow.ledger'))
        ->assertStatus(200)
        ->assertJsonStructure(['status', 'data' => ['data', 'meta', 'links']]);
});

test('admin can resolve dispute with partial refund and negative balance recovery', function () {
    $pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/DISP01',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'completed',
        'subtotal' => 200000.00,
        'tanggal_booking' => Carbon::yesterday()->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 20000.00,
        'pendapatan_mitra' => 180000.00,
        'total_bayar' => 205000.00,
    ]);

    $pembayaran = $pesanan->pembayaran()->create([
        'amount' => 205000.00,
        'status' => 'success',
    ]);

    // Mitra had 0 pending, and already withdrew all available balance (available = 0)
    $this->wallet->update([
        'saldo_pending' => 0.00,
        'saldo_available' => 0.00,
        'total_withdrawn' => 180000.00,
    ]);

    $refund = Refund::create([
        'pesanan_id' => $pesanan->id,
        'pembayaran_id' => $pembayaran->id,
        'user_id' => $this->userClimber->id,
        'mitra_id' => $this->mitra->id,
        'nominal' => 205000.00,
        'alasan' => 'Tenda rusak bocor parah di pos 3 saat hujan',
        'status' => 'disputed',
        'refund_category' => 'incident',
        'is_disputed' => true,
        'disputed_at' => Carbon::now(),
        'dispute_reason' => 'Mitra menolak tanggung jawab padahal tenda robek dari awal',
        'bank_tujuan' => 'BCA',
        'rekening_tujuan' => '0987654321',
        'nama_tujuan' => 'Pendaki Disputing',
    ]);

    // Admin mediates and approves partial refund of 100,000 (mitra share ~ 90,000 deducted into negative)
    $res = $this->actingAs($this->admin)
        ->postJson(route('admin.refunds.process', ['id' => $refund->id]), [
            'status' => 'success',
            'tipe' => 'manual',
            'nominal' => 100000.00,
            'bukti_transfer' => 'proofs/dispute_res.jpg',
            'catatan' => 'Penyelesaian sengketa: Pengembalian dana 50% disetujui.',
        ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.nominal_disetujui', 100000);

    $this->wallet->refresh();
    // Mitra available balance should be negative (-87804.88 approx based on pro-rata mitra portion)
    expect((float) $this->wallet->saldo_available)->toBeLessThan(0);
});

test('admin can trigger force majeure mass auto-refund for closed jalur', function () {
    $pesanan1 = Pesanan::create([
        'invoice' => 'INV/20260903/FM01',
        'user_id' => $this->userClimber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::tomorrow()->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $pesanan1->pembayaran()->create([
        'amount' => 105000.00,
        'status' => 'success',
    ]);

    $this->wallet->update(['saldo_pending' => 90000.00]);

    $res = $this->actingAs($this->admin)
        ->postJson(route('admin.refunds.force-majeure'), [
            'jalur_id' => $this->jalur->id,
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
            'alasan' => 'Erupsi gunung dan penutupan total jalur oleh Balai Taman Nasional.',
        ]);

    $res->assertStatus(200)
        ->assertJsonPath('data.total_refunded', 1);

    $pesanan1->refresh();
    expect($pesanan1->status)->toBe('refunded');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(0.00);

    expect(Refund::where('pesanan_id', $pesanan1->id)->where('refund_category', 'force_majeure')->exists())->toBeTrue();
});
