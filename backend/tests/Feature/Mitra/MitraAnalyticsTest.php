<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\KuotaHarianTiket;
use App\Models\Logbook;
use App\Models\Mitra;
use App\Models\MitraStaff;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\PesananAnggota;
use App\Models\Produk;
use App\Models\ProdukTiket;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Slamet',
        'deskripsi' => 'Gunung tertinggi di Jawa Tengah.',
        'tinggi_mdpl' => 3428,
        'lokasi' => 'Purbalingga, Jawa Tengah',
        'foto' => 'gunungs/slamet.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Jalur Bambangan',
        'deskripsi' => 'Jalur via Bambangan Purbalingga.',
        'titik_awal_mdpl' => '1500 MDPL',
        'titik_akhir_mdpl' => '3428 MDPL',
        'waktu_tempuh' => '8 Jam',
        'status' => 'open',
        'panjang_jalur' => '9.0 Km',
        'tingkat_kesulitan' => 'sulit',
    ]);

    $this->mitraUser = User::factory()->create([
        'role' => 'mitra',
        'name' => 'Mitra Slamet',
        'email' => 'mitra.slamet@test.com',
    ]);
    $this->mitraUser->markEmailAsVerified();

    $this->mitra = Mitra::create([
        'user_id' => $this->mitraUser->id,
        'nama_pemilik' => 'Pak Slamet',
        'telepon' => '081234567890',
        'alamat' => 'Bambangan, Purbalingga',
        'status' => 'aktif',
        'nik' => '3303123456780001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Pak Slamet',
        'bank' => 'Bank BRI',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Bambangan Utama',
        'latitude' => '-7.241234',
        'longitude' => '109.214321',
        'jam_operasional' => '24 Jam',
    ]);

    // Wallet & Withdrawals
    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_available' => 750000.00,
        'saldo_pending' => 250000.00,
    ]);

    Withdrawal::create([
        'mitra_id' => $this->mitra->id,
        'wallet_id' => $this->wallet->id,
        'nominal' => 500000.00,
        'status' => 'completed',
        'bank' => 'Bank BRI',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Pak Slamet',
        'completed_at' => now()->subDays(2),
    ]);

    // Product & Ticket Quota
    $this->produk = Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tiket Bambangan',
        'kategori' => 'ticket',
        'harga' => 35000.00,
        'is_active' => true,
    ]);

    $this->produkTiket = ProdukTiket::create([
        'produk_id' => $this->produk->id,
        'jalur_id' => $this->jalur->id,
        'jam_buka' => '06:00',
        'jam_tutup' => '18:00',
    ]);

    $today = now()->toDateString();
    KuotaHarianTiket::create([
        'produk_tiket_id' => $this->produkTiket->id,
        'tanggal' => $today,
        'kuota_total' => 150,
        'kuota_tersisa' => 130,
    ]);

    // Low stock rental product
    Produk::create([
        'basecamp_id' => $this->basecamp->id,
        'nama_produk' => 'Tenda Dome 4P',
        'kategori' => 'rental',
        'harga' => 60000.00,
        'stok' => 2, // Low stock < 3
        'is_active' => true,
    ]);

    // Staff
    MitraStaff::create([
        'mitra_id' => $this->mitra->id,
        'basecamp_id' => $this->basecamp->id,
        'nama' => 'Staff Ready',
        'role' => 'guide',
        'telepon' => '081233334444',
        'is_available' => true,
    ]);

    MitraStaff::create([
        'mitra_id' => $this->mitra->id,
        'basecamp_id' => $this->basecamp->id,
        'nama' => 'Staff On Duty',
        'role' => 'porter',
        'telepon' => '081233335555',
        'is_available' => false,
    ]);

    // Climber & Orders
    $this->climber = User::factory()->create(['role' => 'pendaki']);

    $this->pesananPaid = Pesanan::create([
        'invoice' => 'INV/SLAMET/001',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'subtotal' => 70000.00,
        'tanggal_booking' => $today,
        'pendapatan_mitra' => 63000.00,
        'total_bayar' => 75000.00,
    ]);

    $this->pembayaranPaid = Pembayaran::create([
        'pesanan_id' => $this->pesananPaid->id,
        'metode' => 'qris',
        'provider' => 'xendit',
        'reference_id' => 'xnd_inv_001',
        'amount' => 75000.00,
        'paid_amount' => 75000.00,
        'status' => 'success',
        'paid_at' => now(),
    ]);

    PesananAnggota::create([
        'pesanan_id' => $this->pesananPaid->id,
        'nama_anggota' => 'Pendaki 1',
        'nik_identitas' => '3301111111110001',
        'telepon' => '081200001111',
    ]);
    PesananAnggota::create([
        'pesanan_id' => $this->pesananPaid->id,
        'nama_anggota' => 'Pendaki 2',
        'nik_identitas' => '3301111111110002',
        'telepon' => '081200001112',
    ]);

    // On-going order
    $this->pesananOnGoing = Pesanan::create([
        'invoice' => 'INV/SLAMET/002',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'on_going',
        'subtotal' => 35000.00,
        'tanggal_booking' => $today,
        'pendapatan_mitra' => 31500.00,
        'total_bayar' => 40000.00,
    ]);

    PesananAnggota::create([
        'pesanan_id' => $this->pesananOnGoing->id,
        'nama_anggota' => 'Pendaki On-going',
        'nik_identitas' => '3301111111110003',
        'telepon' => '081200001113',
    ]);

    // Pending Logbook & Pending Refund
    Logbook::create([
        'pesanan_id' => $this->pesananOnGoing->id,
        'user_id' => $this->climber->id,
        'foto_summit' => 'logbooks/summit_1.jpg',
        'catatan_pendaki' => 'Sampai puncak tugu!',
        'status_validasi' => 'pending',
    ]);

    Refund::create([
        'pesanan_id' => $this->pesananPaid->id,
        'pembayaran_id' => $this->pembayaranPaid->id,
        'mitra_id' => $this->mitra->id,
        'user_id' => $this->climber->id,
        'nominal' => 70000.00,
        'alasan' => 'Ada keperluan mendadak',
        'status' => 'pending',
    ]);
});

test('mitra can retrieve complete analytics summary metrics', function () {
    $response = $this->actingAs($this->mitraUser)
        ->getJson(route('mitra.analytics.summary'));

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Ringkasan metrik analitik mitra berhasil dimuat.',
        'data' => [
            'financial' => [
                'saldo_available' => 750000,
                'saldo_pending' => 250000,
                'total_penarikan' => 500000,
                'total_pendapatan_bersih' => 94500, // 63000 + 31500
                'total_transaksi_paid' => 2,
            ],
            'operations_today' => [
                'pendaki_berangkat_hari_ini' => 3,
                'pendaki_sedang_mendaki' => 1,
                'total_kuota_hari_ini' => 150,
                'kuota_terpakai_hari_ini' => 20,
                'sisa_kuota_hari_ini' => 130,
                'status_jalur' => 'open',
            ],
            'action_queues' => [
                'pesanan_paid_count' => 1,
                'logbook_pending_count' => 1,
                'refund_pending_count' => 1,
            ],
            'resources' => [
                'total_basecamp' => 1,
                'total_produk_aktif' => 2,
                'total_staf_tersedia' => 1,
                'total_staf_bertugas' => 1,
                'produk_stok_menipis_count' => 1,
            ],
        ],
    ]);
});

test('mitra can filter analytics summary by specific basecamp_id', function () {
    $response = $this->actingAs($this->mitraUser)
        ->getJson(route('mitra.analytics.summary', ['basecamp_id' => $this->basecamp->id]));

    $response->assertStatus(200);
    $response->assertJsonPath('data.resources.total_basecamp', 1);
});

test('mitra cannot filter analytics summary by basecamp_id of another partner', function () {
    $otherUser = User::factory()->create(['role' => 'mitra']);
    $otherMitra = Mitra::create([
        'user_id' => $otherUser->id,
        'nama_pemilik' => 'Other Mitra',
        'telepon' => '081299990000',
        'alamat' => 'Other Address',
        'nik' => '3303999999990002',
        'rekening_bank' => '9999888877',
        'nama_rekening' => 'Other Mitra',
        'bank' => 'Bank BCA',
        'status' => 'aktif',
    ]);
    $otherBasecamp = Basecamp::create([
        'mitra_id' => $otherMitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Other Basecamp',
        'latitude' => '-7.200000',
        'longitude' => '109.200000',
        'jam_operasional' => '24 Jam',
    ]);

    $response = $this->actingAs($this->mitraUser)
        ->getJson(route('mitra.analytics.summary', ['basecamp_id' => $otherBasecamp->id]));

    $response->assertStatus(403);
});

test('non-mitra user cannot access analytics summary', function () {
    $response = $this->actingAs($this->climber)
        ->getJson(route('mitra.analytics.summary'));

    $response->assertStatus(403);
});
