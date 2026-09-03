<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Produk;
use App\Models\ProdukTiket;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('demo seeder creates the full payment test dataset', function () {
    $this->seed(DemoSeeder::class);

    $pendaki = User::where('email', 'pendaki@example.com')->firstOrFail();
    expect($pendaki->role)->toBe('pendaki')
        ->and($pendaki->email_verified_at)->not->toBeNull();

    $userMitra = User::where('email', 'mitra@example.com')->firstOrFail();
    expect($userMitra->role)->toBe('mitra');

    $mitra = Mitra::where('user_id', $userMitra->id)->firstOrFail();
    expect($mitra->status)->toBe('aktif');

    $gunung = Gunung::where('nama_gunung', 'Gunung Gede')->firstOrFail();
    expect($gunung->status)->toBe('aktif');

    $jalur = JalurPendakian::where('nama_jalur', 'Jalur Cibodas')->firstOrFail();
    expect($jalur->status)->toBe('open');

    $basecamp = Basecamp::where('nama_basecamp', 'Basecamp Cibodas Indah')->firstOrFail();
    expect($basecamp->jalur_id)->toBe($jalur->id);

    $produkTiket = Produk::where('nama_produk', 'Tiket Simaksi Cibodas')->firstOrFail();
    expect($produkTiket->kategori)->toBe('ticket')
        ->and($produkTiket->is_active)->toBeTrue();

    ProdukTiket::where('produk_id', $produkTiket->id)->firstOrFail();

    $produkRental = Produk::where('nama_produk', 'Sewa Tenda')->firstOrFail();
    expect($produkRental->kategori)->toBe('rental')
        ->and($produkRental->stok)->toBe(10)
        ->and($produkRental->is_active)->toBeTrue();
});

test('demo seeder is idempotent', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'pendaki@example.com')->count())->toBe(1)
        ->and(Gunung::where('nama_gunung', 'Gunung Gede')->count())->toBe(1)
        ->and(Produk::where('nama_produk', 'Sewa Tenda')->count())->toBe(1);
});
