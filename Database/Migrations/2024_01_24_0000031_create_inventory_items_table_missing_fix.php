<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only create if missing (safe for your current DB state)
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();

                // item_groups table exists, but FK to it can also fail if your DB is weird.
                // We'll store the group id without FK for now, then you can add FK later if you want.
                $table->unsignedBigInteger('item_group_id');

                $table->string('name');
                $table->string('sku')->nullable()->unique();
                $table->string('unit')->default('pcs');
                $table->text('description')->nullable();

                $table->boolean('has_serial')->default(false);

                $table->unsignedInteger('reorder_level')->default(0);
                $table->unsignedInteger('qty_on_hand')->default(0);

                $table->boolean('is_active')->default(true);

                $table->timestamps();

                //  short index name
                $table->index(['item_group_id', 'name'], 'inv_items_group_name_idx');
            });
        }
    }

    public function down(): void
    {
        // Only drop if it exists (safe)
        Schema::dropIfExists('inventory_items');
    }
};
