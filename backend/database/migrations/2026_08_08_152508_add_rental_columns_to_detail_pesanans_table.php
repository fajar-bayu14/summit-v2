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
        Schema::table('detail_pesanans', function (Blueprint $table) {
            $table->date('tanggal_mulai_sewa')->nullable()->after('subtotal');
            $table->date('tanggal_selesai_sewa')->nullable()->after('tanggal_mulai_sewa');
            $table->json('catatan_item')->nullable()->after('tanggal_selesai_sewa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_pesanans', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai_sewa', 'tanggal_selesai_sewa', 'catatan_item']);
        });
    }
};
