<?php

use App\Models\BannerAd;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->user = User::factory()->create(['role' => 'pendaki']);
});

test('public can view active banner ads and impression counter increments', function () {
    $activeBanner = BannerAd::create([
        'judul' => 'Promo Peralatan Camping 50%',
        'gambar' => 'banners/promo_camp.jpg',
        'link_url' => 'https://summit.id/promo',
        'posisi' => 'home_top',
        'tanggal_mulai' => Carbon::now()->subDays(2)->format('Y-m-d'),
        'tanggal_selesai' => Carbon::now()->addDays(5)->format('Y-m-d'),
        'is_active' => true,
        'total_impressions' => 10,
        'total_clicks' => 2,
    ]);

    $expiredBanner = BannerAd::create([
        'judul' => 'Promo Kadaluarsa',
        'gambar' => 'banners/expired.jpg',
        'posisi' => 'home_top',
        'tanggal_mulai' => Carbon::now()->subDays(10)->format('Y-m-d'),
        'tanggal_selesai' => Carbon::now()->subDays(2)->format('Y-m-d'),
        'is_active' => true,
    ]);

    $response = $this->getJson(route('ads.banners.index', ['posisi' => 'home_top']));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.judul', 'Promo Peralatan Camping 50%');

    $activeBanner->refresh();
    expect($activeBanner->total_impressions)->toBe(11); // Incremented
});

test('public can record banner click', function () {
    $banner = BannerAd::create([
        'judul' => 'Promo Diskon Tiket',
        'gambar' => 'banners/promo.jpg',
        'posisi' => 'home_top',
        'tanggal_mulai' => Carbon::now()->subDays(1)->format('Y-m-d'),
        'tanggal_selesai' => Carbon::now()->addDays(5)->format('Y-m-d'),
        'is_active' => true,
        'total_clicks' => 5,
    ]);

    $this->postJson(route('ads.banners.click', ['id' => $banner->id]))
        ->assertStatus(200);

    $banner->refresh();
    expect($banner->total_clicks)->toBe(6);
});

test('admin can create, update, and delete promotional banner ads', function () {
    $file = UploadedFile::fake()->image('banner_hero.jpg');

    // 1. Admin creates banner
    $createRes = $this->actingAs($this->admin)
        ->postJson(route('admin.ads.banners.store'), [
            'judul' => 'Festival Gunung Indonesia 2026',
            'gambar' => $file,
            'link_url' => 'https://summit.id/festival',
            'posisi' => 'home_top',
            'tanggal_mulai' => Carbon::now()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addMonth()->format('Y-m-d'),
            'is_active' => true,
        ]);

    $createRes->assertStatus(201)
        ->assertJsonPath('data.judul', 'Festival Gunung Indonesia 2026');

    $bannerId = $createRes->json('data.id');

    // 2. Admin updates banner
    $updateRes = $this->actingAs($this->admin)
        ->putJson(route('admin.ads.banners.update', ['id' => $bannerId]), [
            'judul' => 'Festival Gunung Indonesia 2026 (Updated)',
            'posisi' => 'mountain_detail',
        ]);

    $updateRes->assertStatus(200)
        ->assertJsonPath('data.judul', 'Festival Gunung Indonesia 2026 (Updated)')
        ->assertJsonPath('data.posisi', 'mountain_detail');

    // 3. Admin deletes banner
    $this->actingAs($this->admin)
        ->deleteJson(route('admin.ads.banners.destroy', ['id' => $bannerId]))
        ->assertStatus(200);

    $this->assertDatabaseMissing('banner_ads', ['id' => $bannerId]);
});

test('non-admin user cannot manage banner ads', function () {
    $file = UploadedFile::fake()->image('banner.jpg');

    $this->actingAs($this->user)
        ->postJson(route('admin.ads.banners.store'), [
            'judul' => 'Unauthorized Banner',
            'gambar' => $file,
            'posisi' => 'home_top',
            'tanggal_mulai' => Carbon::now()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'is_active' => true,
        ])
        ->assertStatus(403);
});
