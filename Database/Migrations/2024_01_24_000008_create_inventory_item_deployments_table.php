<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_item_deployments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            //  item_id without FK for now
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty');
            $table->string('site_code')->nullable();
            $table->string('site_name');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            //  short indexes
            $table->index(['technician_id', 'item_id'], 'inv_dep_tech_item_idx');
            $table->index(['item_id'], 'inv_dep_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_deployments');
    }
};
