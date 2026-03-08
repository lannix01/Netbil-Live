<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();

            $table->string('action'); // received | assigned | deployed

            //  item_id without FK for now
            $table->unsignedBigInteger('item_id');

            // optional FK to units (we can keep it because units table exists)
            $table->unsignedBigInteger('item_unit_id')->nullable();

            $table->unsignedInteger('qty')->nullable();
            $table->string('serial_no')->nullable();

            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('site_code')->nullable();
            $table->string('site_name')->nullable();

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            //  short indexes
            $table->index(['action', 'created_at'], 'inv_log_action_time_idx');
            $table->index(['item_id', 'action'], 'inv_log_item_action_idx');
            $table->index(['from_user_id', 'action'], 'inv_log_from_action_idx');
            $table->index(['to_user_id', 'action'], 'inv_log_to_action_idx');
            $table->index(['item_id'], 'inv_log_item_idx');
        });

        //  add FK to item_units with short name (safe)
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->foreign('item_unit_id', 'inv_log_unit_fk')
                ->references('id')
                ->on('inventory_item_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            try { $table->dropForeign('inv_log_unit_fk'); } catch (\Throwable $e) {}
        });

        Schema::dropIfExists('inventory_logs');
    }
};
