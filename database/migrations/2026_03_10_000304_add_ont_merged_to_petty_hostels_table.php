<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('petty_hostels', 'ont_merged')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->boolean('ont_merged')->default(false)->after('amount_due')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('petty_hostels', 'ont_merged')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->dropColumn('ont_merged');
            });
        }
    }
};
