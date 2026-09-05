<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\MitraStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

    $this->jalur = JalurPendakian::create([
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

    $this->mitraUser = User::factory()->create([
        'role' => 'mitra',
        'name' => 'Mitra Merbabu',
        'email' => 'mitra@merbabu.test',
        'password' => Hash::make('Password123!'),
    ]);
    $this->mitraUser->markEmailAsVerified();

    $this->mitra = Mitra::create([
        'user_id' => $this->mitraUser->id,
        'nama_pemilik' => 'Pak Budi Basecamp',
        'telepon' => '081234567890',
        'alamat' => 'Selo, Boyolali, Jawa Tengah',
        'deskripsi' => 'Pengelola Basecamp Selo Merbabu',
        'status' => 'aktif',
        'npwp' => '12.345.678.9-012.000',
        'nik' => '3309123456780001',
        'rekening_bank' => '1234567890',
        'nama_rekening' => 'Budi Basecamp',
        'bank' => 'Bank BCA',
        'ewallet' => '081234567890',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Selo Merbabu',
        'latitude' => '-7.452312',
        'longitude' => '110.423123',
        'jam_operasional' => '24 Jam',
    ]);

    $this->staff = MitraStaff::create([
        'mitra_id' => $this->mitra->id,
        'basecamp_id' => $this->basecamp->id,
        'nama' => 'Joko Guide',
        'role' => 'guide',
        'telepon' => '089876543210',
        'is_available' => true,
    ]);
});

test('mitra can retrieve their profile details along with full basecamps and staff relations', function () {
    $response = $this->actingAs($this->mitraUser)
        ->getJson(route('profile.show'));

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Profile retrieved successfully.',
        'data' => [
            'id' => $this->mitraUser->id,
            'name' => 'Mitra Merbabu',
            'email' => 'mitra@merbabu.test',
            'role' => 'mitra',
            'mitra' => [
                'id' => $this->mitra->id,
                'nama_pemilik' => 'Pak Budi Basecamp',
                'nik' => '3309123456780001',
                'basecamps' => [
                    [
                        'id' => $this->basecamp->id,
                        'nama_basecamp' => 'Basecamp Selo Merbabu',
                        'jalur' => [
                            'id' => $this->jalur->id,
                            'nama_jalur' => 'Jalur Selo',
                            'gunung' => [
                                'nama_gunung' => 'Gunung Merbabu',
                            ],
                        ],
                    ],
                ],
                'staff' => [
                    [
                        'id' => $this->staff->id,
                        'nama' => 'Joko Guide',
                        'role' => 'guide',
                    ],
                ],
            ],
        ],
    ]);
});

test('mitra can update their business profile details and disbursement accounts successfully', function () {
    $payload = [
        'nama_pemilik' => 'Budi Santoso Baru',
        'telepon' => '081299998888',
        'alamat' => 'Jl. Pandanaran No. 45, Boyolali',
        'deskripsi' => 'Pengelola resmi Basecamp Selo Merbabu Pusat',
        'npwp' => '99.888.777.6-555.000',
        'nik' => '3309123456780001', // retain own nik
        'rekening_bank' => '5554443332',
        'nama_rekening' => 'Budi Santoso Baru',
        'bank' => 'Bank Mandiri',
        'ewallet' => '081299998888',
    ];

    $response = $this->actingAs($this->mitraUser)
        ->putJson(route('mitra.profile.update'), $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Profil mitra berhasil diperbarui.',
        'data' => [
            'id' => $this->mitraUser->id,
            'mitra' => [
                'nama_pemilik' => 'Budi Santoso Baru',
                'telepon' => '081299998888',
                'alamat' => 'Jl. Pandanaran No. 45, Boyolali',
                'deskripsi' => 'Pengelola resmi Basecamp Selo Merbabu Pusat',
                'npwp' => '99.888.777.6-555.000',
                'nik' => '3309123456780001',
                'rekening_bank' => '5554443332',
                'nama_rekening' => 'Budi Santoso Baru',
                'bank' => 'Bank Mandiri',
                'ewallet' => '081299998888',
            ],
        ],
    ]);

    $this->assertDatabaseHas('mitras', [
        'id' => $this->mitra->id,
        'nama_pemilik' => 'Budi Santoso Baru',
        'bank' => 'Bank Mandiri',
        'rekening_bank' => '5554443332',
    ]);
});

test('mitra cannot update profile with NIK or NPWP already used by another partner', function () {
    // Create another partner
    $otherUser = User::factory()->create(['role' => 'mitra']);
    Mitra::create([
        'user_id' => $otherUser->id,
        'nama_pemilik' => 'Other Partner',
        'telepon' => '081111222333',
        'alamat' => 'Other Address',
        'status' => 'aktif',
        'npwp' => '11.111.111.1-111.000',
        'nik' => '3309999999990002',
        'rekening_bank' => '1112223334',
        'nama_rekening' => 'Other Partner',
        'bank' => 'Bank BNI',
    ]);

    // Try updating with other partner's NIK and NPWP
    $response = $this->actingAs($this->mitraUser)
        ->putJson(route('mitra.profile.update'), [
            'nama_pemilik' => 'Budi Duplicate',
            'telepon' => '081234567890',
            'alamat' => 'Jl. Merbabu',
            'npwp' => '11.111.111.1-111.000',
            'nik' => '3309999999990002',
            'rekening_bank' => '1112223334',
            'nama_rekening' => 'Budi Duplicate',
            'bank' => 'Bank BCA',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['npwp', 'nik', 'rekening_bank']);
});

test('non-mitra user cannot access mitra profile update endpoint', function () {
    $pendakiUser = User::factory()->create(['role' => 'pendaki']);
    $pendakiUser->markEmailAsVerified();

    $response = $this->actingAs($pendakiUser)
        ->putJson(route('mitra.profile.update'), [
            'nama_pemilik' => 'Hack Attempt',
            'telepon' => '081234567890',
            'alamat' => 'Unknown',
            'nik' => '1234567890123456',
            'rekening_bank' => '1234567890',
            'nama_rekening' => 'Hacker',
            'bank' => 'Bank BCA',
        ]);

    $response->assertStatus(403);
});

test('unauthenticated guest cannot access mitra profile update endpoint', function () {
    $response = $this->putJson(route('mitra.profile.update'), [
        'nama_pemilik' => 'Guest Attempt',
    ]);

    $response->assertStatus(401);
});

test('mitra can change their password successfully', function () {
    $response = $this->actingAs($this->mitraUser)
        ->putJson(route('profile.password.update'), [
            'current_password' => 'Password123!',
            'new_password' => 'NewSecretPassword123!',
            'new_password_confirmation' => 'NewSecretPassword123!',
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Password updated successfully.',
    ]);

    $this->assertTrue(Hash::check('NewSecretPassword123!', $this->mitraUser->fresh()->password));
});
