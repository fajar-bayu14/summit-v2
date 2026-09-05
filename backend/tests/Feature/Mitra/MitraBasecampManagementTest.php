<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\MitraStaff;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Merbabu',
        'deskripsi' => 'Gunung di Jawa Tengah.',
        'tinggi_mdpl' => 3142,
        'lokasi' => 'Magelang, Jawa Tengah',
        'foto' => 'gunungs/merbabu.jpg',
        'status' => 'aktif',
    ]);

    $this->jalurA = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Selo',
        'deskripsi' => 'Jalur via Selo Boyolali.',
        'titik_awal_mdpl' => '1600 MDPL',
        'titik_akhir_mdpl' => '3142 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'open',
        'panjang_jalur' => '7.5 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->jalurB = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Suwanting',
        'deskripsi' => 'Jalur via Suwanting Magelang.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '3142 MDPL',
        'waktu_tempuh' => '7.5 Jam',
        'status' => 'open',
        'panjang_jalur' => '8.2 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    // Partner A (Authenticated)
    $this->mitraUserA = User::factory()->create([
        'role' => 'mitra',
        'name' => 'Mitra A',
        'email' => 'mitra.a@test.com',
    ]);
    $this->mitraUserA->markEmailAsVerified();

    $this->mitraA = Mitra::create([
        'user_id' => $this->mitraUserA->id,
        'nama_pemilik' => 'Pemilik A',
        'telepon' => '081234567890',
        'alamat' => 'Selo, Boyolali',
        'status' => 'aktif',
        'nik' => '3309111122220001',
        'rekening_bank' => '1111222233',
        'nama_rekening' => 'Mitra A',
        'bank' => 'Bank BCA',
    ]);

    $this->basecampA = Basecamp::create([
        'mitra_id' => $this->mitraA->id,
        'jalur_id' => $this->jalurA->id,
        'nama_basecamp' => 'Basecamp Selo Merbabu A',
        'latitude' => '-7.452312',
        'longitude' => '110.423123',
        'jam_operasional' => '24 Jam',
    ]);

    $this->staffA = MitraStaff::create([
        'mitra_id' => $this->mitraA->id,
        'basecamp_id' => $this->basecampA->id,
        'nama' => 'Staff A',
        'role' => 'petugas',
        'telepon' => '081200001111',
        'is_available' => true,
    ]);

    $this->produkA = Produk::create([
        'basecamp_id' => $this->basecampA->id,
        'nama_produk' => 'Tiket Masuk Selo',
        'kategori' => 'ticket',
        'harga' => 25000.00,
        'stok' => 100,
        'is_active' => true,
    ]);

    // Partner B (Other partner)
    $this->mitraUserB = User::factory()->create([
        'role' => 'mitra',
        'name' => 'Mitra B',
        'email' => 'mitra.b@test.com',
    ]);
    $this->mitraUserB->markEmailAsVerified();

    $this->mitraB = Mitra::create([
        'user_id' => $this->mitraUserB->id,
        'nama_pemilik' => 'Pemilik B',
        'telepon' => '089988776655',
        'alamat' => 'Suwanting, Magelang',
        'status' => 'aktif',
        'nik' => '3309222233330002',
        'rekening_bank' => '2222333344',
        'nama_rekening' => 'Mitra B',
        'bank' => 'Bank Mandiri',
    ]);

    $this->basecampB = Basecamp::create([
        'mitra_id' => $this->mitraB->id,
        'jalur_id' => $this->jalurB->id,
        'nama_basecamp' => 'Basecamp Suwanting Merbabu B',
        'latitude' => '-7.481234',
        'longitude' => '110.381234',
        'jam_operasional' => '06:00 - 21:00',
    ]);
});

test('mitra can list all their managed basecamps with relation and counts', function () {
    $response = $this->actingAs($this->mitraUserA)
        ->getJson(route('mitra.basecamp.index'));

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Daftar basecamp berhasil dimuat.',
        'data' => [
            [
                'id' => $this->basecampA->id,
                'nama_basecamp' => 'Basecamp Selo Merbabu A',
                'jalur' => [
                    'id' => $this->jalurA->id,
                    'nama_jalur' => 'Jalur Selo',
                    'gunung' => [
                        'nama_gunung' => 'Gunung Merbabu',
                    ],
                ],
            ],
        ],
    ]);

    // Ensure partner B basecamp is NOT listed
    $response->assertJsonMissing([
        'id' => $this->basecampB->id,
        'nama_basecamp' => 'Basecamp Suwanting Merbabu B',
    ]);
});

test('mitra can get detail of their specific basecamp', function () {
    $response = $this->actingAs($this->mitraUserA)
        ->getJson(route('mitra.basecamp.show', $this->basecampA->id));

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Detail basecamp berhasil dimuat.',
        'data' => [
            'id' => $this->basecampA->id,
            'nama_basecamp' => 'Basecamp Selo Merbabu A',
            'jalur' => [
                'id' => $this->jalurA->id,
                'nama_jalur' => 'Jalur Selo',
            ],
            'produks' => [
                [
                    'id' => $this->produkA->id,
                    'nama_produk' => 'Tiket Masuk Selo',
                ],
            ],
        ],
    ]);
});

test('mitra cannot view detail of basecamp owned by another partner', function () {
    $response = $this->actingAs($this->mitraUserA)
        ->getJson(route('mitra.basecamp.show', $this->basecampB->id));

    $response->assertStatus(404);
});

test('mitra can update their basecamp operational information', function () {
    $payload = [
        'nama_basecamp' => 'Basecamp Selo Merbabu Updated',
        'latitude' => '-7.459999',
        'longitude' => '110.429999',
        'jam_operasional' => '05:00 - 23:00',
    ];

    $response = $this->actingAs($this->mitraUserA)
        ->putJson(route('mitra.basecamp.update', $this->basecampA->id), $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Data basecamp berhasil diperbarui.',
        'data' => [
            'id' => $this->basecampA->id,
            'nama_basecamp' => 'Basecamp Selo Merbabu Updated',
            'latitude' => '-7.459999',
            'longitude' => '110.429999',
            'jam_operasional' => '05:00 - 23:00',
        ],
    ]);

    $this->assertDatabaseHas('basecamps', [
        'id' => $this->basecampA->id,
        'nama_basecamp' => 'Basecamp Selo Merbabu Updated',
    ]);
});

test('mitra cannot update basecamp owned by another partner', function () {
    $response = $this->actingAs($this->mitraUserA)
        ->putJson(route('mitra.basecamp.update', $this->basecampB->id), [
            'nama_basecamp' => 'Hack Attempt',
        ]);

    $response->assertStatus(404);
});

test('mitra can perform emergency closure and reopen on their trail', function () {
    // 1. Emergency close
    $response = $this->actingAs($this->mitraUserA)
        ->postJson(route('mitra.trails.emergency-close', $this->jalurA->id), [
            'status' => 'close',
            'alasan_penutupan' => 'Badai ekstrem di Pos 3',
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Status jalur berhasil diperbarui.',
        'data' => [
            'id' => $this->jalurA->id,
            'status' => 'close',
        ],
    ]);

    $this->assertEquals('close', $this->jalurA->fresh()->status);

    // 2. Reopen
    $reopenResponse = $this->actingAs($this->mitraUserA)
        ->postJson(route('mitra.trails.emergency-close', $this->jalurA->id), [
            'status' => 'open',
        ]);

    $reopenResponse->assertStatus(200);
    $this->assertEquals('open', $this->jalurA->fresh()->status);
});

test('mitra cannot trigger emergency closure on a trail they do not operate', function () {
    // Partner A tries to close Jalur B (operated by Partner B)
    $response = $this->actingAs($this->mitraUserA)
        ->postJson(route('mitra.trails.emergency-close', $this->jalurB->id), [
            'status' => 'close',
            'alasan_penutupan' => 'Illegal closure attempt',
        ]);

    $response->assertStatus(403);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Anda tidak memiliki hak akses untuk mengelola jalur ini.',
    ]);
});

test('non-mitra user cannot access basecamp management endpoints', function () {
    $pendaki = User::factory()->create(['role' => 'pendaki']);
    $pendaki->markEmailAsVerified();

    $response = $this->actingAs($pendaki)
        ->getJson(route('mitra.basecamp.index'));

    $response->assertStatus(403);
});
