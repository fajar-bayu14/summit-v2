<?php

use App\Models\Basecamp;
use App\Models\ChatRoom;
use App\Models\Gunung;
use App\Models\JalurPendakian;
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
    $this->stranger = User::factory()->create(['role' => 'pendaki']);

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
        'invoice' => 'INV/20260903/CHAT01',
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

test('climber can create or get chat room for their order', function () {
    $response = $this->actingAs($this->climber)
        ->postJson(route('chat.rooms.store'), [
            'pesanan_id' => $this->pesanan->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.pesanan_id', $this->pesanan->id)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('chat_rooms', [
        'pesanan_id' => $this->pesanan->id,
        'pendaki_user_id' => $this->climber->id,
        'mitra_user_id' => $this->userMitra->id,
    ]);
});

test('stranger cannot access or create chat room for unowned order', function () {
    $this->actingAs($this->stranger)
        ->postJson(route('chat.rooms.store'), [
            'pesanan_id' => $this->pesanan->id,
        ])
        ->assertStatus(403);
});

test('participants can send message, attachment, and mark as read', function () {
    $room = ChatRoom::create([
        'pesanan_id' => $this->pesanan->id,
        'pendaki_user_id' => $this->climber->id,
        'mitra_user_id' => $this->userMitra->id,
        'status' => 'active',
    ]);

    // 1. Climber sends text message
    $msgRes = $this->actingAs($this->climber)
        ->postJson(route('chat.messages.store', ['room_id' => $room->id]), [
            'message' => 'Halo pos Cibodas, kami estimasi tiba jam 8 pagi.',
        ]);

    $msgRes->assertStatus(201)
        ->assertJsonPath('data.message', 'Halo pos Cibodas, kami estimasi tiba jam 8 pagi.')
        ->assertJsonPath('data.is_read', false);

    // 2. Mitra sends image attachment reply
    $file = UploadedFile::fake()->image('basecamp_map.jpg');
    $replyRes = $this->actingAs($this->userMitra)
        ->postJson(route('chat.messages.store', ['room_id' => $room->id]), [
            'message' => 'Baik, ini peta pos istirahat.',
            'attachment' => $file,
        ]);

    $replyRes->assertStatus(201)
        ->assertJsonPath('data.is_mine', true);

    // 3. View message history
    $this->actingAs($this->climber)
        ->getJson(route('chat.messages.index', ['room_id' => $room->id]))
        ->assertStatus(200)
        ->assertJsonCount(2, 'data.data');

    // 4. Climber marks messages as read
    $this->actingAs($this->climber)
        ->postJson(route('chat.messages.read', ['room_id' => $room->id]))
        ->assertStatus(200);

    $this->assertDatabaseHas('chat_messages', [
        'id' => $replyRes->json('data.id'),
        'is_read' => true,
    ]);
});

test('cannot send message to closed chat room', function () {
    $room = ChatRoom::create([
        'pesanan_id' => $this->pesanan->id,
        'pendaki_user_id' => $this->climber->id,
        'mitra_user_id' => $this->userMitra->id,
        'status' => 'closed',
    ]);

    $this->actingAs($this->climber)
        ->postJson(route('chat.messages.store', ['room_id' => $room->id]), [
            'message' => 'Halo apakah masih aktif?',
        ])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'ERR_CHAT_ROOM_CLOSED');
});
