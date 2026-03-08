<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'inventory_role')) {
                $table->string('inventory_role')->nullable()->after('password'); // admin | technician
            }

            if (!Schema::hasColumn('users', 'inventory_enabled')) {
                $table->boolean('inventory_enabled')->default(false)->after('inventory_role');
            }

            if (!Schema::hasColumn('users', 'inventory_force_password_change')) {
                $table->boolean('inventory_force_password_change')->default(false)->after('inventory_enabled');
            }

            if (!Schema::hasColumn('users', 'inventory_password_changed_at')) {
                $table->timestamp('inventory_password_changed_at')->nullable()->after('inventory_force_password_change');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'inventory_password_changed_at')) $table->dropColumn('inventory_password_changed_at');
            if (Schema::hasColumn('users', 'inventory_force_password_change')) $table->dropColumn('inventory_force_password_change');
            if (Schema::hasColumn('users', 'inventory_enabled')) $table->dropColumn('inventory_enabled');
            if (Schema::hasColumn('users', 'inventory_role')) $table->dropColumn('inventory_role');
        });
    }
};