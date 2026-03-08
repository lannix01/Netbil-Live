<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_payments', 'spending_id')) {
                $table->unsignedBigInteger('spending_id')->nullable()->after('hostel_id')->index();
                $table->foreign('spending_id')
                    ->references('id')
                    ->on('petty_spendings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            if (Schema::hasColumn('petty_payments', 'spending_id')) {
                $table->dropForeign(['spending_id']);
                $table->dropColumn('spending_id');
            }
        });
    }
};

