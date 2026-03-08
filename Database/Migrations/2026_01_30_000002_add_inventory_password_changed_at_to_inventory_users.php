<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_users', 'inventory_password_changed_at')) {
                $table->timestamp('inventory_password_changed_at')->nullable()->after('inventory_force_password_change');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_users', 'inventory_password_changed_at')) {
                $table->dropColumn('inventory_password_changed_at');
            }
        });
    }
};
