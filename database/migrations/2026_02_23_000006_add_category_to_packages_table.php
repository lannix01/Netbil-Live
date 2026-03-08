<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'category')) {
                $table->string('category', 20)->default('hotspot')->after('name')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
