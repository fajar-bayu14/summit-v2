<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Logbook;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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
        'invoice' => 'INV/20260903/LOG01',
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
});

test('climber can upload summit proof and view logbook detail', function () {
    $file = UploadedFile::fake()->image('summit.jpg', 600, 600);

    $response = $this->actingAs($this->climber)
        ->postJson(route('logbook.store', ['invoice' => $this->pesanan->invoice]), [
            'foto_summit' => $file,
            'catatan_pendaki' => 'Berhasil mencapai puncak Suryakencana!',
            'latitude' => '-6.7900',
            'longitude' => '107.0000',
            'waktu_summit' => '2026-09-05 06:30:00',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status_validasi', 'pending')
        ->assertJsonPath('data.gunung_nama', 'Gunung Gede');

    $this->assertDatabaseHas('logbooks', [
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'status_validasi' => 'pending',
    ]);

    // View logbook
    $this->actingAs($this->climber)
        ->getJson(route('logbook.show', ['invoice' => $this->pesanan->invoice]))
        ->assertStatus(200)
        ->assertJsonPath('data.status_validasi', 'pending');
});

test('climber cannot upload duplicate summit proof for same order', function () {
    Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/photo.jpg',
        'status_validasi' => 'pending',
    ]);

    $file = UploadedFile::fake()->image('summit.jpg');

    $this->actingAs($this->climber)
        ->postJson(route('logbook.store', ['invoice' => $this->pesanan->invoice]), [
            'foto_summit' => $file,
        ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERR_LOGBOOK_ALREADY_EXISTS');
});

test('climber can stream pdf certificate only when logbook is approved', function () {
    $logbook = Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/photo.jpg',
        'status_validasi' => 'pending',
    ]);

    // Pending - forbidden
    $this->actingAs($this->climber)
        ->get(route('logbook.certificate', ['invoice' => $this->pesanan->invoice]))
        ->assertStatus(403);

    // Approved - stream PDF
    $logbook->update(['status_validasi' => 'approved', 'validated_at' => now()]);

    $response = $this->actingAs($this->climber)
        ->get(route('logbook.certificate', ['invoice' => $this->pesanan->invoice]));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});

test('climber can view badges for approved summit logbooks', function () {
    Logbook::create([
        'pesanan_id' => $this->pesanan->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'summit_proofs/photo.jpg',
        'status_validasi' => 'approved',
        'validated_at' => now(),
    ]);

    $this->actingAs($this->climber)
        ->getJson(route('pendaki.badges'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.badge_title', 'Penakluk Gunung Gede');
});
