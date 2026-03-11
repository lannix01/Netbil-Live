<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_hostels', 'ont_site_id')) {
                $table->string('ont_site_id', 64)->nullable()->after('contact_person')->index();
            }

            if (!Schema::hasColumn('petty_hostels', 'ont_site_sn')) {
                $table->string('ont_site_sn', 128)->nullable()->after('ont_site_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (Schema::hasColumn('petty_hostels', 'ont_site_sn')) {
                $table->dropColumn('ont_site_sn');
            }

            if (Schema::hasColumn('petty_hostels', 'ont_site_id')) {
                $table->dropColumn('ont_site_id');
            }
        });
    }
};

