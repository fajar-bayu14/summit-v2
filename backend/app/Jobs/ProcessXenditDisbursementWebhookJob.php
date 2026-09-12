<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Models\Withdrawal;
use App\Services\EscrowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessXenditDisbursementWebhookJob implements ShouldQueue
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
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(EscrowService $escrowService): void
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
        $disbursementId = $payload['id'] ?? null;
        $externalId = $payload['external_id'] ?? null;

        $withdrawal = null;
        if ($externalId) {
            $withdrawalId = (int) str_replace('WD-', '', (string) $externalId);
            $withdrawal = Withdrawal::find($withdrawalId);
        }
        if (! $withdrawal && $disbursementId) {
            $withdrawal = Withdrawal::where('disbursement_id', $disbursementId)->first();
        }

        if (! $withdrawal) {
            $event->update([
                'processing_status' => 'ignored',
                'error_message' => "Catatan penarikan dana tidak ditemukan untuk external_id: {$externalId}, disbursement_id: {$disbursementId}",
            ]);

            Log::warning('Xendit disbursement webhook received for unknown withdrawal', [
                'external_id' => $externalId,
                'disbursement_id' => $disbursementId,
                'event_id' => $event->event_id,
            ]);

            return;
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        if (in_array($status, ['COMPLETED', 'SUCCESS', 'DISBURSED'], true)) {
            $escrowService->settleWithdrawalSuccess($withdrawal, $disbursementId);
        } elseif (in_array($status, ['FAILED', 'REJECTED'], true)) {
            $failureReason = $payload['failure_code'] ?? ($payload['failure_message'] ?? 'Transfer ditolak oleh bank tujuan.');
            $escrowService->handleWithdrawalFailure($withdrawal, $failureReason);
        } elseif ($status === 'REVERSED') {
            $reason = $payload['failure_code'] ?? ($payload['failure_message'] ?? null);
            $escrowService->handleWithdrawalReversal($withdrawal, $reason);
        } else {
            Log::info("Status webhook disbursement Xendit tidak ditangani: {$status}", [
                'withdrawal_id' => $withdrawal->id,
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

        Log::error('ProcessXenditDisbursementWebhookJob gagal diproses secara permanen.', [
            'webhook_event_id' => $this->webhookEventId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
