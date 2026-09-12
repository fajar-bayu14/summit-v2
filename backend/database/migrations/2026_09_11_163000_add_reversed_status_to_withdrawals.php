<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE withdrawals MODIFY COLUMN status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'failed', 'reversed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('inflow_holding', 'release_to_available', 'withdrawal_lock', 'withdrawal_settled', 'withdrawal_refunded', 'refund_deduction', 'disbursement_reversed') NOT NULL");
        }

        if (! Schema::hasColumn('wallet_transactions', 'withdrawal_id')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->foreignId('withdrawal_id')->nullable()->after('pesanan_id')->constrained('withdrawals')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasColumn('wallet_transactions', 'withdrawal_id')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('withdrawal_id');
            });
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE withdrawals MODIFY COLUMN status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'failed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('inflow_holding', 'release_to_available', 'withdrawal_lock', 'withdrawal_settled', 'withdrawal_refunded', 'refund_deduction') NOT NULL");
        }
    }
};
