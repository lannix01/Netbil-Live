<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_item_units', function (Blueprint $table) {
            $table->id();

            // Keep as unsignedBigInteger for now; FK will be added later once we confirm parent id type
            $table->unsignedBigInteger('item_id');

            $table->string('serial_no')->unique();
            $table->string('status')->default('in_store');

            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('assigned_at')->nullable();

            $table->string('deployed_site_code')->nullable();
            $table->string('deployed_site_name')->nullable();
            $table->timestamp('deployed_at')->nullable();

            $table->timestamps();

            $table->index(['item_id', 'status'], 'inv_unit_item_status_idx');
            $table->index(['assigned_to', 'status'], 'inv_unit_assignee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_units');
    }
};
