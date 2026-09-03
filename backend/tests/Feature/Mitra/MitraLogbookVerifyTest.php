<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Logbook;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->climber = User::factory()->create(['role' => 'pendaki']);
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

    $this->pesanan = Pesanan::create([
        'invoice' => 'INV/20260903/LOGMITRA01',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 100000.00,
        'tanggal_booking' => Carbon::now()->addDays(2)->format('Y-m-d'),
        'diskon' => 0.00,
        'biaya_layanan_user' => 5000.00,
        'komisi_admin' => 10000.00,
        'pendapatan_mitra' => 90000.00,
        'total_bayar' => 105000.00,
    ]);

    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 90000.00,
        'saldo_available' => 0.00,
        'total_withdrawn' => 0.00,
    ]);
});

test('mitra can list logbook verification submissions', function () {
    Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/photo.jpg',
        'status_validasi' => 'pending',
    ]);

    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.logbooks.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');
});

test('mitra can approve summit proof, completing order and releasing escrow', function () {
    $logbook = Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/photo.jpg',
        'status_validasi' => 'pending',
    ]);

    $response = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.logbooks.verify', ['id' => $logbook->id]), [
            'status_validasi' => 'approved',
            'catatan_petugas' => 'Foto summit sah dan terverifikasi di pos check-out.',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status_validasi', 'approved');

    $this->pesanan->refresh();
    expect($this->pesanan->status)->toBe('completed');
    expect($this->pesanan->status_escrow)->toBe('released');

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(0.00);
    expect((float) $this->wallet->saldo_available)->toBe(90000.00);
});

test('mitra can reject summit proof with reason', function () {
    $logbook = Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/fake.jpg',
        'status_validasi' => 'pending',
    ]);

    $response = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.logbooks.verify', ['id' => $logbook->id]), [
            'status_validasi' => 'rejected',
            'catatan_petugas' => 'Foto bukan di puncak Gunung Gede.',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status_validasi', 'rejected');

    $this->pesanan->refresh();
    expect($this->pesanan->status)->toBe('paid'); // not completed

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(90000.00); // still holding
    expect((float) $this->wallet->saldo_available)->toBe(0.00);
});
