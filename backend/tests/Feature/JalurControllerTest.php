<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('admin can list trails with pagination and search filter', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Gunung Putri',
        'deskripsi' => 'Jalur cepat.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'open',
        'panjang_jalur' => '6 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.jalur.index', ['search' => 'Putri']));

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.nama_jalur', 'Jalur Gunung Putri');
});

test('admin can list trails filtered by gunung_id and status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung1 = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $gunung2 = Gunung::create([
        'nama_gunung' => 'Gunung Prau',
        'deskripsi' => 'Gunung Jawa Tengah.',
        'tinggi_mdpl' => 2565,
        'lokasi' => 'Wonosobo, Jawa Tengah',
        'foto' => 'gunungs/prau.jpg',
        'status' => 'aktif',
    ]);

    JalurPendakian::create([
        'gunung_id' => $gunung1->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    JalurPendakian::create([
        'gunung_id' => $gunung1->id,
        'nama_jalur' => 'Jalur Putri Close',
        'deskripsi' => 'Jalur ditutup.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'close',
        'panjang_jalur' => '6 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    JalurPendakian::create([
        'gunung_id' => $gunung2->id,
        'nama_jalur' => 'Jalur Patak Banteng',
        'deskripsi' => 'Jalur populer.',
        'titik_awal_mdpl' => '2000 MDPL',
        'titik_akhir_mdpl' => '2565 MDPL',
        'waktu_tempuh' => '4 Jam',
        'status' => 'open',
        'panjang_jalur' => '4 Km',
        'tingkat_kesulitan' => 'mudah',
    ]);

    // Filter by gunung1 and status open
    $response = $this->actingAs($admin)->getJson(route('admin.jalur.index', [
        'gunung_id' => $gunung1->id,
        'status' => 'open',
    ]));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.nama_jalur', 'Jalur Cibodas');
});

test('admin can get details of a specific trail', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.jalur.show', ['id' => $jalur->id]));

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $jalur->id)
        ->assertJsonPath('data.nama_jalur', 'Jalur Cibodas')
        ->assertJsonPath('data.gunung.nama_gunung', 'Gunung Gede');
});

test('admin can create a trail for a mountain', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.jalur.store'), [
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air terjun dan air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('jalur_pendakians', [
        'nama_jalur' => 'Jalur Cibodas',
        'gunung_id' => $gunung->id,
    ]);
});

test('admin can update a trail', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Putri',
        'deskripsi' => 'Jalur curam.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'open',
        'panjang_jalur' => '6 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    $response = $this->actingAs($admin)->putJson(route('admin.jalur.update', ['id' => $jalur->id]), [
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Putri Updated',
        'deskripsi' => 'Jalur terjal dengan pemandangan luas.',
        'titik_awal_mdpl' => '1400 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '6 Jam',
        'status' => 'close',
        'panjang_jalur' => '6 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('jalur_pendakians', [
        'id' => $jalur->id,
        'nama_jalur' => 'Jalur Putri Updated',
        'status' => 'close',
    ]);
});

test('admin can quickly update trail status (open/close) via patch', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $response = $this->actingAs($admin)->patchJson(route('admin.jalur.update-status', ['id' => $jalur->id]), [
        'status' => 'close',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'close');

    $this->assertDatabaseHas('jalur_pendakians', [
        'id' => $jalur->id,
        'status' => 'close',
    ]);
});

test('admin cannot delete trail if active basecamp is associated', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $mitraUser = User::factory()->create(['role' => 'mitra']);
    $mitra = Mitra::factory()->create(['user_id' => $mitraUser->id]);

    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Cibodas',
        'deskripsi' => 'Jalur air panas.',
        'titik_awal_mdpl' => '1300 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '7 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    Basecamp::create([
        'mitra_id' => $mitra->id,
        'jalur_id' => $jalur->id,
        'nama_basecamp' => 'Basecamp Cibodas Official',
        'latitude' => '-6.7900',
        'longitude' => '106.9900',
        'jam_operasional' => '24 Jam',
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('admin.jalur.destroy', ['id' => $jalur->id]));

    $response->assertStatus(400)
        ->assertJsonPath('status', 'error');

    $this->assertDatabaseHas('jalur_pendakians', [
        'id' => $jalur->id,
    ]);
});

test('admin can delete a trail without dependencies', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $gunung = Gunung::create([
        'nama_gunung' => 'Gunung Gede',
        'deskripsi' => 'Gunung Jawa Barat.',
        'tinggi_mdpl' => 2958,
        'lokasi' => 'Cianjur, Jawa Barat',
        'foto' => 'gunungs/gede.jpg',
        'status' => 'aktif',
    ]);

    $jalur = JalurPendakian::create([
        'gunung_id' => $gunung->id,
        'nama_jalur' => 'Jalur Salabintana',
        'deskripsi' => 'Jalur pacet.',
        'titik_awal_mdpl' => '1000 MDPL',
        'titik_akhir_mdpl' => '2958 MDPL',
        'waktu_tempuh' => '12 Jam',
        'status' => 'open',
        'panjang_jalur' => '12 Km',
        'tingkat_kesulitan' => 'ekstrem',
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('admin.jalur.destroy', ['id' => $jalur->id]));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('jalur_pendakians', [
        'id' => $jalur->id,
    ]);
});

test('climber cannot perform CRUD operations on trails', function () {
    $user = User::factory()->create(['role' => 'pendaki']);
    $user->markEmailAsVerified();

    $response = $this->actingAs($user)->postJson(route('admin.jalur.store'), [
        'nama_jalur' => 'Jalur Ilegal',
    ]);
    $response->assertStatus(403);
});

test('getting non-existent trail returns 404', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->getJson(route('admin.jalur.show', ['id' => 9999]));
    $response->assertStatus(404);
});
