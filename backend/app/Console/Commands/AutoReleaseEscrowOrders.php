<?php

namespace App\Console\Commands;

use App\Models\Pesanan;
use App\Services\EscrowService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoReleaseEscrowOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escrow:auto-release {--days=2 : Grace period in days after booking date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically complete overdue ongoing or paid hiking orders and release escrow funds to partner wallets';

    /**
     * Execute the console command.
     */
    public function handle(EscrowService $escrowService): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days)->startOfDay();

        $this->info("Scanning for overdue hiking orders on or before: {$cutoffDate->toDateString()} ({$days} days grace period)...");

        // Eligible orders:
        // 1. Status 'on_going' or 'paid' with status_escrow 'holding'
        // 2. Booking date is older than or equal to cutoffDate
        // 3. No active pending refund requests (to avoid auto-releasing disputed money)
        $orders = Pesanan::whereIn('status', ['on_going', 'paid'])
            ->where('status_escrow', 'holding')
            ->whereDate('tanggal_booking', '<=', $cutoffDate)
            ->whereDoesntHave('refunds', function ($query) {
                $query->whereIn('status', ['pending', 'diproses']);
            })
            ->with(['basecamp.mitra', 'details'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No overdue orders found for auto-release.');

            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} orders eligible for auto-release.");

        $processed = 0;
        $failed = 0;

        foreach ($orders as $pesanan) {
            try {
                DB::transaction(function () use ($pesanan, $escrowService) {
                    $pesanan->update([
                        'status' => 'completed',
                        'status_escrow' => 'released',
                    ]);

                    // Complete operational items
                    $pesanan->details()
                        ->where('status_operasional', '!=', 'cancelled')
                        ->update(['status_operasional' => 'completed']);

                    // Release escrow funds to partner available balance
                    $escrowService->releaseEscrowToAvailable($pesanan);
                });

                Log::info("Escrow auto-released for order #{$pesanan->id} ({$pesanan->invoice}) after grace period.", [
                    'pesanan_id' => $pesanan->id,
                    'invoice' => $pesanan->invoice,
                    'pendapatan_mitra' => $pesanan->pendapatan_mitra,
                ]);

                $this->line(" - Released #{$pesanan->invoice} (Rp ".number_format((float) $pesanan->pendapatan_mitra, 0, ',', '.').')');
                $processed++;
            } catch (\Throwable $e) {
                Log::error("Failed to auto-release escrow for order #{$pesanan->id}: ".$e->getMessage());
                $this->error(" x Failed #{$pesanan->invoice}: ".$e->getMessage());
                $failed++;
            }
        }

        $this->info("Auto-release complete. Processed: {$processed}, Failed: {$failed}.");

        return Command::SUCCESS;
    }
}
