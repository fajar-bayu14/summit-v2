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
        Schema::create('mitra_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('mitras')->cascadeOnDelete();
            $table->foreignId('basecamp_id')->nullable()->constrained('basecamps')->nullOnDelete();
            $table->string('nama');
            $table->enum('role', ['guide', 'porter', 'petugas'])->default('guide');
            $table->string('telepon');
            $table->boolean('is_available')->default(true);
            $table->text('jadwal_tugas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_staff');
    }
};
