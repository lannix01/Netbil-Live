<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_user_id')
                ->constrained('inventory_users')
                ->cascadeOnDelete();

            $table->string('action', 40);
            $table->string('route_name')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['inventory_user_id', 'created_at'], 'inv_act_user_time_idx');
            $table->index(['action', 'created_at'], 'inv_act_action_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_activity_logs');
    }
};
