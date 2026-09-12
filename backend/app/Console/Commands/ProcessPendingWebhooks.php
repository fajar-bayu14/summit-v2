<?php

namespace App\Console\Commands;

use App\Jobs\ProcessXenditPaymentWebhookJob;
use App\Models\WebhookEvent;
use App\Services\PesananService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('webhooks:process-pending {--sync : Process pending webhook events synchronously without waiting for queue}')]
#[Description('Process pending or stuck webhook events and drain the webhooks queue.')]
class ProcessPendingWebhooks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PesananService $pesananService): int
    {
        $this->info('Mencari event webhook yang berstatus pending...');

        $pendingEvents = WebhookEvent::whereIn('processing_status', ['pending', 'failed'])
            ->orderBy('id', 'asc')
            ->get();

        if ($pendingEvents->isEmpty()) {
            $this->info('Tidak ada event webhook yang tertahan.');
        } else {
            $this->info("Ditemukan {$pendingEvents->count()} event webhook tertahan.");

            if ($this->option('sync')) {
                foreach ($pendingEvents as $event) {
                    $this->line("Memproses synchronous event #{$event->id} ({$event->resource_id})...");
                    (new ProcessXenditPaymentWebhookJob($event->id))->handle($pesananService);
                }
            } else {
                foreach ($pendingEvents as $event) {
                    ProcessXenditPaymentWebhookJob::dispatch($event->id);
                }
            }
        }

        $this->info('Memproses antrian database queue (webhooks, default)...');
        Artisan::call('queue:work', [
            '--queue' => 'webhooks,default',
            '--stop-when-empty' => true,
            '--tries' => 3,
        ]);

        $this->line(Artisan::output());
        $this->info('Selesai memproses webhook dan antrian.');

        return self::SUCCESS;
    }
}
