<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('inventory_item_groups')) {
            Schema::create('inventory_item_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable()->unique();
                $table->text('description')->nullable();
                $table->timestamps();

                // optional: active flag (useful later)
                // $table->boolean('is_active')->default(true);
            });
        } else {
            // If table exists but missing columns (safe upgrades)
            Schema::table('inventory_item_groups', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_item_groups', 'code')) {
                    $table->string('code')->nullable()->unique()->after('name');
                }
                if (!Schema::hasColumn('inventory_item_groups', 'description')) {
                    $table->text('description')->nullable()->after('code');
                }
            });
        }
    }

    public function down(): void
    {
        // We do NOT drop in down() to avoid accidental loss of live data.
        // If you truly want drop behavior later, we can add it then.
    }
};
