<?php

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\JalurPendakian;
use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Refund;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EscrowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->gunung = Gunung::create([
        'nama_gunung' => 'Gunung Slamet',
        'deskripsi' => 'Gunung Jawa Tengah.',
        'tinggi_mdpl' => 3428,
        'lokasi' => 'Banyumas',
        'foto' => 'gunungs/slamet.jpg',
        'status' => 'aktif',
    ]);

    $this->jalur = JalurPendakian::create([
        'gunung_id' => $this->gunung->id,
        'nama_jalur' => 'Bambangan',
        'deskripsi' => 'Jalur Bambangan.',
        'titik_awal_mdpl' => '1500 MDPL',
        'titik_akhir_mdpl' => '3428 MDPL',
        'waktu_tempuh' => '8 Jam',
        'status' => 'open',
        'panjang_jalur' => '7 Km',
        'tingkat_kesulitan' => 'sedang',
    ]);

    $this->userMitra = User::factory()->create(['role' => 'mitra']);
    $this->mitra = Mitra::create([
        'user_id' => $this->userMitra->id,
        'nama_pemilik' => 'Pak Mitra',
        'telepon' => '081234567890',
        'alamat' => 'Basecamp',
        'nik' => '3201234567890001',
        'rekening_bank' => '123456',
        'nama_rekening' => 'Pak Mitra',
        'bank' => 'BCA',
        'status' => 'aktif',
    ]);

    $this->basecamp = Basecamp::create([
        'mitra_id' => $this->mitra->id,
        'jalur_id' => $this->jalur->id,
        'nama_basecamp' => 'Basecamp Bambangan',
        'latitude' => '-7.123',
        'longitude' => '109.123',
        'jam_operasional' => '24 Jam',
    ]);

    $this->wallet = Wallet::create([
        'mitra_id' => $this->mitra->id,
        'saldo_pending' => 0.00,
        'saldo_available' => 0.00,
        'total_withdrawn' => 0.00,
    ]);

    $this->climber = User::factory()->create(['role' => 'pendaki']);
});

test('auto release command completes overdue orders and releases escrow funds', function () {
    $escrowService = app(EscrowService::class);

    // Overdue order (3 days ago)
    $overdueOrder = Pesanan::create([
        'invoice' => 'INV/20260910/OVERDUE',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'on_going',
        'status_escrow' => 'holding',
        'subtotal' => 100000.00,
        'total_bayar' => 100000.00,
        'pendapatan_mitra' => 90000.00,
        'tanggal_booking' => Carbon::now()->subDays(3)->toDateString(),
    ]);
    $escrowService->recordPaymentHolding($overdueOrder);

    // Recent order (today)
    $recentOrder = Pesanan::create([
        'invoice' => 'INV/20260914/RECENT',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'on_going',
        'status_escrow' => 'holding',
        'subtotal' => 50000.00,
        'total_bayar' => 50000.00,
        'pendapatan_mitra' => 45000.00,
        'tanggal_booking' => Carbon::now()->toDateString(),
    ]);
    $escrowService->recordPaymentHolding($recentOrder);

    // Disputed order (3 days ago, but has pending refund)
    $disputedOrder = Pesanan::create([
        'invoice' => 'INV/20260910/DISPUTED',
        'user_id' => $this->climber->id,
        'basecamp_id' => $this->basecamp->id,
        'jalur_id' => $this->jalur->id,
        'status' => 'paid',
        'status_escrow' => 'holding',
        'subtotal' => 80000.00,
        'total_bayar' => 80000.00,
        'pendapatan_mitra' => 70000.00,
        'tanggal_booking' => Carbon::now()->subDays(3)->toDateString(),
    ]);
    $pembayaran = $disputedOrder->pembayaran()->create([
        'amount' => 80000.00,
        'payment_status' => 'settled',
        'payment_channel' => 'BCA_VA',
    ]);
    Refund::create([
        'pesanan_id' => $disputedOrder->id,
        'pembayaran_id' => $pembayaran->id,
        'mitra_id' => $this->mitra->id,
        'nominal' => 80000.00,
        'alasan' => 'Ada komplain cuaca',
        'status' => 'pending',
    ]);
    $escrowService->recordPaymentHolding($disputedOrder);

    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_pending)->toBe(205000.00) // 90k + 45k + 70k
        ->and((float) $this->wallet->saldo_available)->toBe(0.00);

    // Run command with default 2 days grace period
    $this->artisan('escrow:auto-release --days=2')
        ->expectsOutputToContain('Auto-release complete')
        ->assertSuccessful();

    // Check overdue order is completed and released
    $overdueOrder->refresh();
    expect($overdueOrder->status)->toBe('completed')
        ->and($overdueOrder->status_escrow)->toBe('released');

    // Recent order remains on_going and holding
    $recentOrder->refresh();
    expect($recentOrder->status)->toBe('on_going')
        ->and($recentOrder->status_escrow)->toBe('holding');

    // Disputed order remains paid and holding
    $disputedOrder->refresh();
    expect($disputedOrder->status)->toBe('paid')
        ->and($disputedOrder->status_escrow)->toBe('holding');

    // Wallet balance updated: 90k moved to available, 115k remains in pending
    $this->wallet->refresh();
    expect((float) $this->wallet->saldo_available)->toBe(90000.00)
        ->and((float) $this->wallet->saldo_pending)->toBe(115000.00);
});
