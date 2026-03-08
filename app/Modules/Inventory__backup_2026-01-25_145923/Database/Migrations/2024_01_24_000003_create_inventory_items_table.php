<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_group_id')->constrained('inventory_item_groups')->cascadeOnDelete();

            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('unit')->default('pcs');
            $table->text('description')->nullable();

            $table->boolean('has_serial')->default(false);

            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('qty_on_hand')->default(0); // store qty (reduced on assignment)

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['item_group_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
