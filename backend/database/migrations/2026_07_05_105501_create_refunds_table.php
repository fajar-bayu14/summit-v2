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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanans')->cascadeOnDelete();
            $table->foreignId('pembayaran_id')->constrained('pembayarans')->cascadeOnDelete();
            $table->foreignId('mitra_id')->nullable()->constrained('mitras')->nullOnDelete();
            $table->string('reference_refund_id')->nullable()->unique(); // ID Refund dari Payment Gateway
            $table->enum('tipe', ['auto', 'manual'])->default('auto');
            $table->string('refund_category')->default('pre_trip'); // pre_trip, incident, force_majeure, dispute
            $table->decimal('nominal', 15, 2);
            $table->decimal('nominal_disetujui', 15, 2)->nullable();
            $table->text('alasan');
            $table->enum('status', [
                'pending',
                'approved_by_mitra',
                'rejected_by_mitra',
                'disputed',
                'success',
                'failed',
                'rejected',
            ])->default('pending');
            $table->string('bank_tujuan')->nullable();
            $table->string('rekening_tujuan')->nullable();
            $table->string('nama_tujuan')->nullable();
            $table->string('bukti_transfer')->nullable();
            $table->text('mitra_alasan_penolakan')->nullable();
            $table->timestamp('mitra_reviewed_at')->nullable();
            $table->boolean('is_disputed')->default(false);
            $table->timestamp('disputed_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->text('admin_catatan')->nullable();
            $table->json('raw_response')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
