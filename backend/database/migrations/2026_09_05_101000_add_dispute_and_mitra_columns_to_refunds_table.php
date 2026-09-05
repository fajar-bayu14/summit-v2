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
        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'mitra_id')) {
                $table->foreignId('mitra_id')->nullable()->after('pembayaran_id')->constrained('mitras')->nullOnDelete();
            }
            if (! Schema::hasColumn('refunds', 'refund_category')) {
                $table->string('refund_category')->default('pre_trip')->after('tipe');
            }
            if (! Schema::hasColumn('refunds', 'nominal_disetujui')) {
                $table->decimal('nominal_disetujui', 15, 2)->nullable()->after('nominal');
            }
            if (! Schema::hasColumn('refunds', 'mitra_alasan_penolakan')) {
                $table->text('mitra_alasan_penolakan')->nullable()->after('bukti_transfer');
            }
            if (! Schema::hasColumn('refunds', 'mitra_reviewed_at')) {
                $table->timestamp('mitra_reviewed_at')->nullable()->after('mitra_alasan_penolakan');
            }
            if (! Schema::hasColumn('refunds', 'is_disputed')) {
                $table->boolean('is_disputed')->default(false)->after('mitra_reviewed_at');
            }
            if (! Schema::hasColumn('refunds', 'disputed_at')) {
                $table->timestamp('disputed_at')->nullable()->after('is_disputed');
            }
            if (! Schema::hasColumn('refunds', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable()->after('disputed_at');
            }
            if (! Schema::hasColumn('refunds', 'admin_catatan')) {
                $table->text('admin_catatan')->nullable()->after('dispute_reason');
            }
            // Ensure status column supports all 2-tier statuses
            $table->string('status', 50)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (Schema::hasColumn('refunds', 'mitra_id')) {
                $table->dropForeign(['mitra_id']);
                $table->dropColumn('mitra_id');
            }
            $columnsToDrop = array_filter([
                'refund_category',
                'nominal_disetujui',
                'mitra_alasan_penolakan',
                'mitra_reviewed_at',
                'is_disputed',
                'disputed_at',
                'dispute_reason',
                'admin_catatan',
            ], fn ($col) => Schema::hasColumn('refunds', $col));

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
