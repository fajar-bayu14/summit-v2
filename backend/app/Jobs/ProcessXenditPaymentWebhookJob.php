<?php

namespace App\Jobs;

use App\Models\PaymentWebhookLog;
use App\Models\Pesanan;
use App\Models\WebhookEvent;
use App\Services\PesananService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessXenditPaymentWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $webhookEventId
    ) {
        $this->onQueue((string) config('services.xendit.queue', 'webhooks'));
    }

    /**
     * Execute the job.
     */
    public function handle(PesananService $pesananService): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event) {
            return;
        }

        if ($event->processing_status === 'processed') {
            return;
        }

        $event->update(['processing_status' => 'processing']);

        $payload = $event->payload;
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $externalId = (string) ($payload['external_id'] ?? ($data['external_id'] ?? ($data['reference_id'] ?? '')));

        if ($externalId === '') {
            $event->update([
                'processing_status' => 'ignored',
                'error_message' => 'Missing external_id in payload.',
            ]);

            return;
        }

        $pesanan = Pesanan::where('invoice', $externalId)
            ->with(['pembayaran', 'details.produk', 'details.produk.tiket'])
            ->first();

        $mergedPayload = array_merge($data, $payload);
        $status = strtoupper((string) ($payload['status'] ?? ($data['status'] ?? '')));

        // Maintain backward compatibility with payment_webhook_logs table
        PaymentWebhookLog::create([
            'pesanan_id' => $pesanan?->id,
            'pembayaran_id' => $pesanan?->pembayaran?->id,
            'provider' => 'xendit',
            'event' => $event->event_type,
            'external_id' => $externalId,
            'status_raw' => $status,
            'payload' => $payload,
            'ip_address' => $event->ip_address,
            'is_valid' => true,
        ]);

        if (! $pesanan) {
            $event->update([
                'processing_status' => 'ignored',
                'error_message' => "Invoice {$externalId} tidak ditemukan di database Summit.",
            ]);

            Log::warning('Xendit payment webhook received for unknown invoice', [
                'external_id' => $externalId,
                'event_id' => $event->event_id,
            ]);

            return;
        }

        if ($status === 'PAID') {
            $pesananService->markPaid($pesanan, $mergedPayload);
        } elseif ($status === 'EXPIRED') {
            $pesananService->markExpired($pesanan);
        } else {
            Log::info("Status webhook Xendit tidak ditangani: {$status}", [
                'invoice' => $externalId,
            ]);
        }

        $event->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        $event?->update([
            'processing_status' => 'failed',
            'failed_at' => now(),
            'error_message' => $exception?->getMessage(),
        ]);

        Log::error('ProcessXenditPaymentWebhookJob gagal diproses secara permanen.', [
            'webhook_event_id' => $this->webhookEventId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
