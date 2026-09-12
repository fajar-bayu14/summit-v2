<?php

namespace App\Services;

use App\Models\Mitra;
use App\Models\Pesanan;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EscrowService
{
    /**
     * Get or create wallet for the given partner with lock.
     */
    public function getOrCreateWalletWithLock(Mitra $mitra): Wallet
    {
        return Wallet::where('mitra_id', $mitra->id)->lockForUpdate()->firstOrCreate(
            ['mitra_id' => $mitra->id],
            [
                'saldo_pending' => 0.00,
                'saldo_available' => 0.00,
                'total_withdrawn' => 0.00,
            ]
        );
    }

    /**
     * Record payment holding in escrow (when order is paid by climber).
     */
    public function recordPaymentHolding(Pesanan $pesanan): void
    {
        $mitra = $pesanan->basecamp?->mitra;
        if (! $mitra) {
            return;
        }

        DB::transaction(function () use ($mitra, $pesanan) {
            $wallet = $this->getOrCreateWalletWithLock($mitra);

            // Idempotency check: Jangan menambah holding saldo jika pesanan ini sudah tercatat
            $alreadyRecorded = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('pesanan_id', $pesanan->id)
                ->where('type', 'inflow_holding')
                ->lockForUpdate()
                ->exists();

            if ($alreadyRecorded) {
                return;
            }

            $amount = (float) $pesanan->pendapatan_mitra;

            $wallet->saldo_pending = $this->addMoney($wallet->saldo_pending, $amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'pesanan_id' => $pesanan->id,
                'type' => 'inflow_holding',
                'nominal' => $amount,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Dana pesanan masuk ke escrow holding: '.$pesanan->invoice,
            ]);
        });
    }

    /**
     * Release escrow holding to available balance (when order is completed / summit validated).
     */
    public function releaseEscrowToAvailable(Pesanan $pesanan): void
    {
        $mitra = $pesanan->basecamp?->mitra;
        if (! $mitra) {
            return;
        }

        DB::transaction(function () use ($mitra, $pesanan) {
            $wallet = $this->getOrCreateWalletWithLock($mitra);
            $amount = (float) $pesanan->pendapatan_mitra;

            // Ensure saldo_pending is not negative
            $newPending = $this->subMoney($wallet->saldo_pending, $amount);
            $wallet->saldo_pending = max(0.00, (float) $newPending);
            $wallet->saldo_available = $this->addMoney($wallet->saldo_available, $amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'pesanan_id' => $pesanan->id,
                'type' => 'release_to_available',
                'nominal' => $amount,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Pencairan escrow ke saldo aktif untuk pesanan selesai: '.$pesanan->invoice,
            ]);
        });
    }

    /**
     * Request withdrawal by partner from available balance.
     */
    public function requestWithdrawal(Mitra $mitra, float $amount, ?string $notes = null): Withdrawal
    {
        if ($amount < 50000.00) {
            throw new InvalidArgumentException('Minimal penarikan dana adalah Rp 50.000,00.');
        }

        return DB::transaction(function () use ($mitra, $amount, $notes) {
            $wallet = $this->getOrCreateWalletWithLock($mitra);

            if ((float) $wallet->saldo_available < $amount) {
                throw new InvalidArgumentException('Saldo aktif tidak mencukupi untuk melakukan penarikan.');
            }

            // Lock the amount from available balance
            $wallet->saldo_available = bcsub((string) $wallet->saldo_available, (string) $amount, 2);
            $wallet->save();

            $withdrawal = Withdrawal::create([
                'mitra_id' => $mitra->id,
                'wallet_id' => $wallet->id,
                'nominal' => $amount,
                'biaya_admin' => 0.00,
                'bank' => $mitra->bank,
                'rekening_bank' => $mitra->rekening_bank,
                'nama_rekening' => $mitra->nama_rekening,
                'status' => 'pending',
                'catatan' => $notes,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal_lock',
                'nominal' => $amount,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Penguncian saldo untuk pengajuan penarikan dana #'.$withdrawal->id,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Reject a pending withdrawal and return funds to available balance.
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $reason) {
            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->saldo_available = $this->addMoney($wallet->saldo_available, $withdrawal->nominal);
            $wallet->save();

            $withdrawal->update([
                'status' => 'rejected',
                'alasan_penolakan' => $reason,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal_refunded',
                'nominal' => $withdrawal->nominal,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Pengembalian saldo akibat penolakan penarikan dana #'.$withdrawal->id.': '.$reason,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Settle withdrawal as completed (from Xendit webhook callback or manual).
     */
    public function settleWithdrawalSuccess(Withdrawal $withdrawal, ?string $disbursementId = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $disbursementId) {
            $withdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            // Idempotency: Jika sudah completed, tidak perlu mutasi ulang
            if ($withdrawal->status === 'completed') {
                return $withdrawal;
            }

            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->total_withdrawn = $this->addMoney($wallet->total_withdrawn, $withdrawal->nominal);
            $wallet->save();

            $withdrawal->update([
                'status' => 'completed',
                'disbursement_id' => $disbursementId ?? $withdrawal->disbursement_id,
                'completed_at' => now(),
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'type' => 'withdrawal_settled',
                'nominal' => $withdrawal->nominal,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Penarikan dana berhasil ditransfer via Xendit Iris Disbursement #'.$withdrawal->id,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Handle failed disbursement from Xendit and revert balance.
     */
    public function handleWithdrawalFailure(Withdrawal $withdrawal, string $failureReason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $failureReason) {
            $withdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            // Idempotency: Jika sudah failed, jangan kembalikan saldo dua kali
            if ($withdrawal->status === 'failed') {
                return $withdrawal;
            }

            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            $wallet->saldo_available = $this->addMoney($wallet->saldo_available, $withdrawal->nominal);
            $wallet->save();

            $withdrawal->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'type' => 'withdrawal_refunded',
                'nominal' => $withdrawal->nominal,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Pengembalian saldo akibat kegagalan transfer Xendit #'.$withdrawal->id.': '.$failureReason,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Handle reversed disbursement from Xendit (money clawed back after completed).
     */
    public function handleWithdrawalReversal(Withdrawal $withdrawal, ?string $reason = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $reason) {
            $withdrawal = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            // Idempotency: Jika sudah reversed, return early
            if ($withdrawal->status === 'reversed') {
                return $withdrawal;
            }

            if ($withdrawal->status !== 'completed') {
                throw new InvalidArgumentException('Hanya penarikan dana dengan status completed yang dapat dibatalkan (reversed).');
            }

            $wallet = Wallet::where('id', $withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            // Kurangi total_withdrawn dan kembalikan dana ke saldo aktif mitra
            $newWithdrawn = $this->subMoney($wallet->total_withdrawn, $withdrawal->nominal);
            $wallet->total_withdrawn = max(0.00, (float) $newWithdrawn);
            $wallet->saldo_available = $this->addMoney($wallet->saldo_available, $withdrawal->nominal);
            $wallet->save();

            $reversalNote = $reason ?? 'Dana transfer penarikan ditarik kembali oleh pihak bank / Xendit (REVERSED).';

            $withdrawal->update([
                'status' => 'reversed',
                'failure_reason' => $reversalNote,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'type' => 'disbursement_reversed',
                'nominal' => $withdrawal->nominal,
                'saldo_pending_after' => $wallet->saldo_pending,
                'saldo_available_after' => $wallet->saldo_available,
                'catatan' => 'Reversal penarikan dana #'.$withdrawal->id.': '.$reversalNote,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Add monetary values safely with 2 decimal precision.
     */
    protected function addMoney(string|float|int $a, string|float|int $b): string
    {
        return function_exists('bcadd')
            ? bcadd((string) $a, (string) $b, 2)
            : number_format((float) $a + (float) $b, 2, '.', '');
    }

    /**
     * Subtract monetary values safely with 2 decimal precision.
     */
    protected function subMoney(string|float|int $a, string|float|int $b): string
    {
        return function_exists('bcsub')
            ? bcsub((string) $a, (string) $b, 2)
            : number_format((float) $a - (float) $b, 2, '.', '');
    }
}
