<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\KuotaHarianTiket;
use App\Models\Mitra;
use App\Models\Produk;
use App\Models\ProdukTiket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->userMitra = User::factory()->create(['role' => 'mitra']);
    $this->mitra = Mitra::create([
        'user_id' => $this->userMitra->id,
        'nama_pemilik' => 'Pak Mitra',
        'telepon' => '08123456789',
        'alamat' => 'Basecamp Cibodas',
        'status' => 'aktif',
        'nik' => '3201234567890001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Pak Mitra',
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

    $this->produkTicket = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tiket Masuk Cibodas',
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
        'nama_produk' => 'Sewa Tenda Dome',
        'kategori' => 'rental',
        'harga' => 35000.00,
        'stok' => 10,
        'is_active' => true,
    ]);
});

test('mitra can toggle active status of a product', function () {
    $this->actingAs($this->userMitra)
        ->patchJson(route('mitra.products.toggle-status', ['id' => $this->produkRental->id]), [
            'is_active' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('produks', [
        'id' => $this->produkRental->id,
        'is_active' => 0,
    ]);
});

test('mitra can update rental stock', function () {
    $this->actingAs($this->userMitra)
        ->patchJson(route('mitra.products.stock', ['id' => $this->produkRental->id]), [
            'stok' => 25,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.stok', 25);

    $this->assertDatabaseHas('produks', [
        'id' => $this->produkRental->id,
        'stok' => 25,
    ]);
});

test('mitra can set batch daily quotas for a ticket product', function () {
    $startDate = Carbon::now()->addDays(2)->format('Y-m-d');
    $endDate = Carbon::now()->addDays(5)->format('Y-m-d');

    $this->actingAs($this->userMitra)
        ->postJson(route('mitra.products.quotas.batch', ['id' => $this->produkTicket->id]), [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kuota_total' => 150,
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('kuota_harian_tikets', [
        'produk_tiket_id' => $this->produkTiketDetail->id,
        'tanggal' => Carbon::parse($startDate)->startOfDay(),
        'kuota_total' => 150,
        'kuota_tersisa' => 150,
    ]);

    // Check list quotas
    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.products.quotas', ['id' => $this->produkTicket->id, 'start_date' => $startDate, 'end_date' => $endDate]))
        ->assertStatus(200)
        ->assertJsonCount(4, 'data');
});

test('mitra can update quota for a specific date', function () {
    $date = Carbon::now()->addDays(3)->format('Y-m-d');
    $quota = KuotaHarianTiket::create([
        'produk_tiket_id' => $this->produkTiketDetail->id,
        'tanggal' => $date,
        'kuota_total' => 100,
        'kuota_tersisa' => 90, // 10 already booked
    ]);

    $this->actingAs($this->userMitra)
        ->putJson(route('mitra.quotas.update', ['quota_id' => $quota->id]), [
            'kuota_total' => 120,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.kuota_total', 120)
        ->assertJsonPath('data.kuota_tersisa', 110); // 120 - 10

    // Cannot set lower than used quota (10)
    $this->actingAs($this->userMitra)
        ->putJson(route('mitra.quotas.update', ['quota_id' => $quota->id]), [
            'kuota_total' => 5,
        ])
        ->assertStatus(422);
});

test('mitra can emergency close and reopen their trail', function () {
    $this->actingAs($this->userMitra)
        ->postJson(route('mitra.trails.emergency-close', ['id' => $this->jalur->id]), [
            'status' => 'close',
            'alasan_penutupan' => 'Cuaca badai dan longsor.',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'close');

    $this->assertDatabaseHas('jalur_pendakians', [
        'id' => $this->jalur->id,
        'status' => 'close',
    ]);

    // Reopen
    $this->actingAs($this->userMitra)
        ->postJson(route('mitra.trails.emergency-close', ['id' => $this->jalur->id]), [
            'status' => 'open',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'open');
});

test('mitra can create, view, update, and delete staff', function () {
    // 1. Create staff
    $createRes = $this->actingAs($this->userMitra)
        ->postJson(route('mitra.staff.store'), [
            'basecamp_id' => $this->basecamp->id,
            'nama' => 'Joko Guide',
            'role' => 'guide',
            'telepon' => '081299887766',
            'is_available' => true,
            'jadwal_tugas' => 'Senin - Minggu',
        ]);

    $createRes->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.nama', 'Joko Guide');

    $staffId = $createRes->json('data.id');

    // 2. List staff
    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.staff.index'))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // 3. Show staff
    $this->actingAs($this->userMitra)
        ->getJson(route('mitra.staff.show', ['id' => $staffId]))
        ->assertStatus(200)
        ->assertJsonPath('data.nama', 'Joko Guide');

    // 4. Update staff
    $this->actingAs($this->userMitra)
        ->putJson(route('mitra.staff.update', ['id' => $staffId]), [
            'nama' => 'Joko Guide Senior',
            'is_available' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.nama', 'Joko Guide Senior')
        ->assertJsonPath('data.is_available', false);

    // 5. Delete staff
    $this->actingAs($this->userMitra)
        ->deleteJson(route('mitra.staff.destroy', ['id' => $staffId]))
        ->assertStatus(200);

    $this->assertDatabaseMissing('mitra_staff', ['id' => $staffId]);
});
