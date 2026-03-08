<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movement_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('movement_id')->constrained('inventory_movements')->cascadeOnDelete();

            //  item_id without FK for now
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty')->nullable();

            // Unit FK is fine (table exists)
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->string('serial_no')->nullable();

            $table->timestamps();

            //  short indexes
            $table->index(['movement_id', 'item_id'], 'inv_movl_mov_item_idx');
            $table->index(['item_id', 'item_unit_id'], 'inv_movl_item_unit_idx');
            $table->index(['item_id'], 'inv_movl_item_idx');
        });

        //  FK to units with short name
        Schema::table('inventory_movement_lines', function (Blueprint $table) {
            $table->foreign('item_unit_id', 'inv_movl_unit_fk')
                ->references('id')
                ->on('inventory_item_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movement_lines', function (Blueprint $table) {
            try { $table->dropForeign('inv_movl_unit_fk'); } catch (\Throwable $e) {}
        });

        Schema::dropIfExists('inventory_movement_lines');
    }
};
