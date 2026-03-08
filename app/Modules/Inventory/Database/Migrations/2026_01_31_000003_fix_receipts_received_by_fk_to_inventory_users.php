<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock_receipts', function (Blueprint $table) {
            // Drop old FK to users table (name is from your error)
            if (Schema::hasColumn('inventory_stock_receipts', 'received_by')) {
                try {
                    $table->dropForeign('inventory_stock_receipts_received_by_foreign');
                } catch (\Throwable $e) {
                    // ignore if already dropped / name differs
                }
            }
        });

        Schema::table('inventory_stock_receipts', function (Blueprint $table) {
            // Re-add FK to inventory_users
            $table->foreign('received_by')
                ->references('id')
                ->on('inventory_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_receipts', function (Blueprint $table) {
            try {
                $table->dropForeign(['received_by']);
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('inventory_stock_receipts', function (Blueprint $table) {
            $table->foreign('received_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
