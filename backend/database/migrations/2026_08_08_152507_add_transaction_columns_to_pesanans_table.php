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
        Schema::table('pesanans', function (Blueprint $table) {
            $table->date('tanggal_selesai_booking')->nullable()->after('tanggal_booking');
            $table->enum('status_escrow', ['holding', 'eligible', 'released', 'disbursed', 'refunded'])
                ->default('holding')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanans', function (Blueprint $table) {
            $table->dropColumn(['tanggal_selesai_booking', 'status_escrow']);
        });
    }
};
