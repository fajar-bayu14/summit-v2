<?php

namespace Database\Seeders;

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Produk;
use App\Models\ProdukTiket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a minimal catalog dataset for manual payment gateway testing.
     *
     * Run with: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $pendaki = User::firstOrCreate(
            ['email' => 'pendaki@example.com'],
            [
                'name' => 'Pendaki Demo',
                'password' => Hash::make('password'),
                'role' => 'pendaki',
                'email_verified_at' => now(),
            ]
        );

        $userMitra = User::firstOrCreate(
            ['email' => 'mitra@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'mitra',
                'email_verified_at' => now(),
            ]
        );

        $mitra = Mitra::firstOrCreate(
            ['user_id' => $userMitra->id],
            [
                'nama_pemilik' => 'Budi Santoso',
                'telepon' => '081234567890',
                'alamat' => 'Jl. Raya Summit No. 10',
                'status' => 'aktif',
                'nik' => '3201234567890001',
                'rekening_bank' => '1234567890',
                'nama_rekening' => 'Budi Santoso',
                'bank' => 'Bank BCA',
            ]
        );

        $gunung = Gunung::firstOrCreate(
            ['nama_gunung' => 'Gunung Gede'],
            [
                'deskripsi' => 'Gunung Jawa Barat.',
                'tinggi_mdpl' => 2958,
                'lokasi' => 'Cianjur, Jawa Barat',
                'foto' => 'gunungs/gede.jpg',
                'status' => 'aktif',
            ]
        );

        $jalur = JalurPendakian::firstOrCreate(
            ['nama_jalur' => 'Jalur Cibodas', 'gunung_id' => $gunung->id],
            [
                'deskripsi' => 'Jalur air terjun dan air panas.',
                'titik_awal_mdpl' => '1300 MDPL',
                'titik_akhir_mdpl' => '2958 MDPL',
                'waktu_tempuh' => '7 Jam',
                'status' => 'open',
                'panjang_jalur' => '9.7 Km',
                'tingkat_kesulitan' => 'sedang',
            ]
        );

        $basecamp = Basecamp::firstOrCreate(
            ['nama_basecamp' => 'Basecamp Cibodas Indah'],
            [
                'mitra_id' => $mitra->id,
                'jalur_id' => $jalur->id,
                'latitude' => '-6.793214',
                'longitude' => '107.001234',
                'jam_operasional' => '24 Jam',
            ]
        );

        $produkTiket = Produk::firstOrCreate(
            ['nama_produk' => 'Tiket Simaksi Cibodas'],
            [
                'basecamp_id' => $basecamp->id,
                'kategori' => 'ticket',
                'harga' => 20000.00,
                'is_active' => true,
            ]
        );

        ProdukTiket::firstOrCreate(
            ['produk_id' => $produkTiket->id],
            [
                'jalur_id' => $jalur->id,
                'jam_buka' => '07:00:00',
                'jam_tutup' => '17:00:00',
            ]
        );

        $produkRental = Produk::firstOrCreate(
            ['nama_produk' => 'Sewa Tenda'],
            [
                'basecamp_id' => $basecamp->id,
                'kategori' => 'rental',
                'harga' => 35000.00,
                'stok' => 10,
                'is_active' => true,
            ]
        );

        $this->command->info('DemoSeeder selesai:');
        $this->command->info('  Login pendaki : pendaki@example.com / password');
        $this->command->info('  Login mitra   : mitra@example.com / password');
        $this->command->info("  basecamp_id   : {$basecamp->id}");
        $this->command->info("  jalur_id      : {$jalur->id}");
        $this->command->info("  tiket produk  : {$produkTiket->id} (harga 20000)");
        $this->command->info("  rental produk : {$produkRental->id} (harga 35000, stok 10)");
        $this->command->info("  Pendaki ID    : {$pendaki->id}");
    }
}
