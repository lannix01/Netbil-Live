<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('petty_hostels', 'contact_person')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->string('contact_person')->nullable()->after('hostel_name')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('petty_hostels', 'contact_person')) {
            Schema::table('petty_hostels', function (Blueprint $table) {
                $table->dropColumn('contact_person');
            });
        }
    }
};
