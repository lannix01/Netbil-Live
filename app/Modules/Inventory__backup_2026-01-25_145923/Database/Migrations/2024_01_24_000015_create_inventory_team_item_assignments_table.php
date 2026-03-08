<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_team_item_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')->constrained('inventory_teams')->cascadeOnDelete();

            // item_id WITHOUT FK (your DB had FK issues on inventory_items)
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty_allocated')->default(0);
            $table->unsignedInteger('qty_deployed')->default(0);

            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // short indexes
            $table->unique(['team_id', 'item_id'], 'inv_tteam_item_uq');
            $table->index(['team_id', 'is_active'], 'inv_tteam_active_idx');
            $table->index(['item_id'], 'inv_tteam_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_team_item_assignments');
    }
};