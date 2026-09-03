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
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->nullable()->constrained('pesanans')->nullOnDelete();
            $table->foreignId('pembayaran_id')->nullable()->constrained('pembayarans')->nullOnDelete();
            $table->string('provider')->nullable(); // e.g. 'xendit', 'duitku'
            $table->string('event')->nullable(); // e.g. 'invoice.paid', 'payment.settled'
            $table->string('external_id')->nullable(); // Reference/Invoice ID from gateway
            $table->string('status_raw')->nullable(); // Status raw from gateway
            $table->json('payload'); // Raw JSON payload
            $table->string('ip_address')->nullable(); // For verification audit
            $table->boolean('is_valid')->default(false); // Did it pass signature verification?
            $table->text('error_message')->nullable(); // Detail error if validation/processing fails
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
