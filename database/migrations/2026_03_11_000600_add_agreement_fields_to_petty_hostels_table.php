<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_hostels', 'agreement_type')) {
                $table->string('agreement_type', 32)->default('none')->after('ont_site_sn')->index();
            }

            if (!Schema::hasColumn('petty_hostels', 'agreement_label')) {
                $table->string('agreement_label', 255)->nullable()->after('agreement_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (Schema::hasColumn('petty_hostels', 'agreement_label')) {
                $table->dropColumn('agreement_label');
            }

            if (Schema::hasColumn('petty_hostels', 'agreement_type')) {
                $table->dropIndex(['agreement_type']);
                $table->dropColumn('agreement_type');
            }
        });
    }
};
