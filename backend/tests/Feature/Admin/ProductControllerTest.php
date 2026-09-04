<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\KuotaHarianTiket;
use App\Models\Mitra;
use App\Models\Produk;
use App\Models\ProdukOpentrip;
use App\Models\ProdukTiket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->pendaki = User::factory()->create(['role' => 'pendaki']);

    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Merbabu',
        'deskripsi' => 'Gunung di Jawa Tengah.',
        'tinggi_mdpl' => 3145,
        'lokasi' => 'Boyolali, Jawa Tengah',
        'foto' => 'gunungs/merbabu.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Selo',
        'deskripsi' => 'Jalur Selo populer',
        'titik_awal_mdpl' => '1600 MDPL',
        'titik_akhir_mdpl' => '3145 MDPL',
        'waktu_tempuh' => '5 Jam',
        'panjang_jalur' => '5.5 Km',
        'status' => 'open',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->userMitra1 = User::factory()->create(['role' => 'mitra', 'name' => 'Mitra Merbabu']);
    $this->mitra1 = Mitra::create([
        'user_id' => $this->userMitra1->id,
        'nama_pemilik' => 'Mitra Merbabu Jaya',
        'telepon' => '081234567891',
        'alamat' => 'Selo, Boyolali',
        'status' => 'aktif',
        'nik' => '3309123456780001',
        'npwp' => '12.345.678.9-012.000',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Mitra Merbabu Jaya',
        'bank' => 'Bank BCA',
    ]);

    $this->userMitra2 = User::factory()->create(['role' => 'mitra', 'name' => 'Mitra Sindoro']);
    $this->mitra2 = Mitra::create([
        'user_id' => $this->userMitra2->id,
        'nama_pemilik' => 'Mitra Sindoro Perkasa',
        'telepon' => '081234567892',
        'alamat' => 'Kledung, Temanggung',
        'status' => 'aktif',
        'nik' => '3309123456780002',
        'npwp' => '12.345.678.9-012.001',
        'rekening_bank' => '0987654321',
        'nama_rekening' => 'Mitra Sindoro Perkasa',
        'bank' => 'Bank Mandiri',
    ]);

    $this->basecamp1 = Basecamp::create([
        'mitra_id' => $this->mitra1->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Selo Merbabu',
        'latitude' => '-7.441234',
        'longitude' => '110.421234',
        'jam_operasional' => '24 Jam',
    ]);

    $this->basecamp2 = Basecamp::create([
        'mitra_id' => $this->mitra2->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Kledung',
        'latitude' => '-7.341234',
        'longitude' => '110.021234',
        'jam_operasional' => '08:00 - 20:00',
    ]);

    // Product 1: Ticket
    $this->produk1 = Produk::create([
        'basecamp_id' => $this->basecamp1->id,
        'nama_produk' => 'Tiket Masuk Selo Merbabu',
        'kategori' => 'ticket',
        'deskripsi' => 'Tiket resmi SIMAKSI Merbabu via Selo',
        'harga' => 25000,
        'stok' => 100,
        'satuan' => 'orang',
        'is_active' => true,
    ]);
    $this->tiket1 = ProdukTiket::create([
        'produk_id' => $this->produk1->id,
        'jalur_id' => $this->jalur->id,
        'jam_buka' => '07:00:00',
        'jam_tutup' => '17:00:00',
    ]);
    KuotaHarianTiket::create([
        'produk_tiket_id' => $this->tiket1->id,
        'tanggal' => now()->toDateString(),
        'kuota_total' => 100,
        'kuota_tersisa' => 80,
    ]);

    // Product 2: Rental
    $this->produk2 = Produk::create([
        'basecamp_id' => $this->basecamp1->id,
        'nama_produk' => 'Sewa Tenda Dome Kapasitas 4',
        'kategori' => 'rental',
        'deskripsi' => 'Tenda dome double layer',
        'harga' => 60000,
        'stok' => 15,
        'satuan' => 'hari',
        'is_active' => true,
    ]);

    // Product 3: Open Trip (Mitra 2)
    $this->produk3 = Produk::create([
        'basecamp_id' => $this->basecamp2->id,
        'nama_produk' => 'Open Trip Merbabu Sunrise Experience',
        'kategori' => 'opentrip',
        'deskripsi' => 'Paket pendakian all-inclusive 2H1M',
        'harga' => 450000,
        'stok' => 20,
        'satuan' => 'orang',
        'is_active' => true,
    ]);
    ProdukOpentrip::create([
        'produk_id' => $this->produk3->id,
        'tanggal_berangkat' => now()->addDays(7)->toDateString(),
        'tanggal_pulang' => now()->addDays(9)->toDateString(),
        'meeting_point' => 'Basecamp Selo',
        'minimal_peserta' => 5,
        'maksimal_peserta' => 20,
        'sisa_kursi' => 12,
    ]);
});

test('unauthenticated users cannot access admin products', function () {
    $this->getJson('/api/v1/admin/products')->assertStatus(401);
    $this->getJson("/api/v1/admin/products/{$this->produk1->id}")->assertStatus(401);
});

test('non-admin users cannot access admin products', function () {
    $this->actingAs($this->pendaki, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertStatus(403);
});

test('admin can list all products across all basecamps and partners', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/products')
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'basecamp_id',
                        'nama_produk',
                        'kategori',
                        'harga',
                        'stok',
                        'satuan',
                        'is_active',
                        'basecamp' => [
                            'id',
                            'nama_basecamp',
                            'mitra' => [
                                'id',
                                'nama_pemilik',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ],
        ]);

    expect($response->json('data.data'))->toHaveCount(3);
    expect($response->json('data.meta.total'))->toBe(3);
});

test('admin can filter products by mitra_id', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/v1/admin/products?mitra_id={$this->mitra1->id}")
        ->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(2);
    expect($response->json('data.meta.total'))->toBe(2);
    $items = $response->json('data.data');
    foreach ($items as $item) {
        expect($item['basecamp']['mitra']['id'])->toBe($this->mitra1->id);
    }
});

test('admin can filter products by basecamp_id', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/v1/admin/products?basecamp_id={$this->basecamp2->id}")
        ->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.meta.total'))->toBe(1);
    expect($response->json('data.data.0.nama_produk'))->toBe('Open Trip Merbabu Sunrise Experience');
});

test('admin can filter products by kategori', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/products?kategori=ticket')
        ->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.meta.total'))->toBe(1);
    expect($response->json('data.data.0.kategori'))->toBe('ticket');
});

test('admin can search products by keyword', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/products?search=Tenda')
        ->assertStatus(200);

    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.meta.total'))->toBe(1);
    expect($response->json('data.data.0.nama_produk'))->toContain('Tenda');
});

test('admin can get single product detail with relations', function () {
    $response = $this->actingAs($this->admin, 'sanctum')
        ->getJson("/api/v1/admin/products/{$this->produk1->id}")
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $this->produk1->id,
                'nama_produk' => 'Tiket Masuk Selo Merbabu',
                'kategori' => 'ticket',
            ],
        ]);

    expect($response->json('data.tiket.kuotas'))->toBeArray();
    expect(count($response->json('data.tiket.kuotas')))->toBe(1);
});

test('admin gets 404 for non-existent product', function () {
    $this->actingAs($this->admin, 'sanctum')
        ->getJson('/api/v1/admin/products/99999')
        ->assertStatus(404);
});
