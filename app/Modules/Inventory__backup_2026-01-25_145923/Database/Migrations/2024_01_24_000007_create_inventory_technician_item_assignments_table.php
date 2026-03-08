<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_technician_item_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            //  item_id without FK for now (FK added later after we confirm inventory_items.id type)
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty_allocated')->default(0);
            $table->unsignedInteger('qty_deployed')->default(0);

            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            //  short index names (avoid MySQL 64-char identifier limit)
            $table->unique(['technician_id', 'item_id'], 'inv_tia_tech_item_uq');
            $table->index(['technician_id', 'is_active'], 'inv_tia_tech_active_idx');
            $table->index(['item_id'], 'inv_tia_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_technician_item_assignments');
    }
};
