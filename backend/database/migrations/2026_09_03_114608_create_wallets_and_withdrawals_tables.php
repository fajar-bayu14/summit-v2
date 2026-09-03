<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->unique()->constrained('mitras')->cascadeOnDelete();
            $table->decimal('saldo_pending', 15, 2)->default(0.00);
            $table->decimal('saldo_available', 15, 2)->default(0.00);
            $table->decimal('total_withdrawn', 15, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('pesanan_id')->nullable()->constrained('pesanans')->nullOnDelete();
            $table->enum('type', [
                'inflow_holding',
                'release_to_available',
                'withdrawal_lock',
                'withdrawal_settled',
                'withdrawal_refunded',
                'refund_deduction',
            ]);
            $table->decimal('nominal', 15, 2);
            $table->decimal('saldo_pending_after', 15, 2);
            $table->decimal('saldo_available_after', 15, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('disbursement_id')->nullable()->index();
            $table->decimal('nominal', 15, 2);
            $table->decimal('biaya_admin', 15, 2)->default(0.00);
            $table->string('bank');
            $table->string('rekening_bank');
            $table->string('nama_rekening');
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'])->default('pending');
            $table->text('catatan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
