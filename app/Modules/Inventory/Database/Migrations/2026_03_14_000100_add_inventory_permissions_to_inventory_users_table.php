<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_users', 'inventory_permissions')) {
                $table->json('inventory_permissions')->nullable()->after('inventory_role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_users', 'inventory_permissions')) {
                $table->dropColumn('inventory_permissions');
            }
        });
    }
};
