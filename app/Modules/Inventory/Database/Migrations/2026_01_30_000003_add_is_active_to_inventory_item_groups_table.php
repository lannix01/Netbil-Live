<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_item_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_item_groups', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_item_groups', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_item_groups', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
