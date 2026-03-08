<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_team_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_id')->constrained('inventory_teams')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            $table->string('role')->default('member'); // leader | member
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // short constraints/indexes
            $table->unique(['team_id', 'technician_id'], 'inv_team_member_uq');
            $table->index(['technician_id', 'is_active'], 'inv_team_member_tech_act_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_team_members');
    }
};