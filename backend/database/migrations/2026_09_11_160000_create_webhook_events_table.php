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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('xendit');
            $table->string('event_id', 128);
            $table->string('event_type', 64);
            $table->string('resource_type', 32);
            $table->string('resource_id', 64)->nullable();
            $table->json('payload');
            $table->string('ip_address', 45)->nullable();
            $table->enum('verification_status', ['verified', 'unverified', 'invalid'])->default('unverified');
            $table->enum('processing_status', ['pending', 'processing', 'processed', 'failed', 'ignored'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id'], 'uk_provider_event');
            $table->index(['resource_type', 'resource_id'], 'idx_resource');
            $table->index(['processing_status', 'created_at'], 'idx_proc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
