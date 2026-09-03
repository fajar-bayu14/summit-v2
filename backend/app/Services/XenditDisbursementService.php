<?php

namespace App\Services;

use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XenditDisbursementService
{
    /**
     * Create disbursement request to Xendit API.
     */
    public function createDisbursement(Withdrawal $withdrawal): array
    {
        $secretKey = (string) config('services.xendit.secret_key');
        $idempotencyKey = 'disb_'.Str::slug($withdrawal->id.'_'.now()->timestamp);

        // If secret key is empty (test/local environment), simulate successful creation
        if (empty($secretKey) || app()->environment('testing')) {
            $disbursementId = 'disb_mock_'.Str::random(16);
            $withdrawal->update([
                'status' => 'processing',
                'disbursement_id' => $disbursementId,
            ]);

            return [
                'id' => $disbursementId,
                'status' => 'PENDING',
                'amount' => (float) $withdrawal->nominal,
                'bank_code' => $withdrawal->bank,
                'account_holder_name' => $withdrawal->nama_rekening,
                'account_number' => $withdrawal->rekening_bank,
            ];
        }

        $response = Http::withBasicAuth($secretKey, '')
            ->withHeaders([
                'X-IDEMPOTENCY-KEY' => $idempotencyKey,
            ])
            ->post('https://api.xendit.co/disbursements', [
                'external_id' => 'WD-'.$withdrawal->id,
                'amount' => (float) $withdrawal->nominal,
                'bank_code' => strtoupper($withdrawal->bank),
                'account_holder_name' => $withdrawal->nama_rekening,
                'account_number' => $withdrawal->rekening_bank,
                'description' => 'Penarikan Dana Mitra Summit v2 #'.$withdrawal->id,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $withdrawal->update([
                'status' => 'processing',
                'disbursement_id' => $data['id'] ?? null,
            ]);

            return $data;
        }

        $withdrawal->update(['status' => 'failed', 'failure_reason' => $response->body()]);
        throw new \RuntimeException('Gagal memproses disbursement Xendit: '.$response->body());
    }
}
