<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_spendings', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_spendings', 'meter_no')) {
                $table->string('meter_no', 64)->nullable()->after('reference');
                $table->index('meter_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_spendings', function (Blueprint $table) {
            if (Schema::hasColumn('petty_spendings', 'meter_no')) {
                $table->dropIndex(['meter_no']);
                $table->dropColumn('meter_no');
            }
        });
    }
};
