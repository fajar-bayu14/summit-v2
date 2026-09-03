<?php

namespace App\Console\Commands;

use App\Services\PesananService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-pending')]
#[Description('Expire pending orders whose payment window has passed and restore their stock.')]
class ExpirePendingOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PesananService $pesananService): int
    {
        $expired = $pesananService->expireStale();

        $this->info("Expired {$expired} pending order(s).");

        return self::SUCCESS;
    }
}
