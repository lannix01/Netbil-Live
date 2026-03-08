<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add optional team_id on deployments
        if (Schema::hasTable('inventory_item_deployments') && !Schema::hasColumn('inventory_item_deployments', 'team_id')) {
            Schema::table('inventory_item_deployments', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('technician_id');
                $table->index(['team_id', 'item_id'], 'inv_dep_team_item_idx');
            });
        }

        // Add optional team_id on logs
        if (Schema::hasTable('inventory_logs') && !Schema::hasColumn('inventory_logs', 'team_id')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('to_user_id');
                $table->index(['team_id', 'action'], 'inv_log_team_action_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_item_deployments') && Schema::hasColumn('inventory_item_deployments', 'team_id')) {
            Schema::table('inventory_item_deployments', function (Blueprint $table) {
                $table->dropIndex('inv_dep_team_item_idx');
                $table->dropColumn('team_id');
            });
        }

        if (Schema::hasTable('inventory_logs') && Schema::hasColumn('inventory_logs', 'team_id')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                $table->dropIndex('inv_log_team_action_idx');
                $table->dropColumn('team_id');
            });
        }
    }
};