<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function colInfo(string $table, string $column): ?object
    {
        return DB::selectOne("
            SELECT COLUMN_TYPE, IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        ", [DB::getDatabaseName(), $table, $column]);
    }

    /**
     * Make column NULLABLE using raw ALTER, without doctrine/dbal.
     * Keeps the existing COLUMN_TYPE intact.
     */
    private function makeNullable(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return;

        $info = $this->colInfo($table, $column);
        if (!$info) return;

        if (($info->IS_NULLABLE ?? 'YES') === 'YES') return;

        $type = $info->COLUMN_TYPE; // includes unsigned, etc
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$type} NULL");
    }

    /**
     * Null any orphan FK values that don't exist in reference table.
     */
    private function nullOrphans(string $table, string $column, string $refTable, string $refCol = 'id'): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return;
        if (!Schema::hasTable($refTable) || !Schema::hasColumn($refTable, $refCol)) return;

        DB::statement("
            UPDATE `{$table}` t
            LEFT JOIN `{$refTable}` r ON r.`{$refCol}` = t.`{$column}`
            SET t.`{$column}` = NULL
            WHERE t.`{$column}` IS NOT NULL
              AND r.`{$refCol}` IS NULL
        ");
    }

    /**
     * Drop FK constraints for a given table+column if they exist (whatever the FK name is).
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return;

        $dbName = DB::getDatabaseName();

        $rows = DB::select("
            SELECT kcu.CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = ?
              AND kcu.TABLE_NAME = ?
              AND kcu.COLUMN_NAME = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $table, $column]);

        foreach ($rows as $r) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$r->name}`");
        }
    }

    /**
     * Add FK (only if column exists). If column is NOT NULL, SET NULL is invalid -> make it nullable first.
     */
    private function addFk(string $table, string $column, string $refTable, string $refCol = 'id', string $onDelete = 'SET NULL'): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return;

        $final = strtoupper($onDelete);

        if ($final === 'SET NULL') {
            // ensure it can be NULL (otherwise MySQL forces NOT NULL and fails later)
            $this->makeNullable($table, $column);
        }

        Schema::table($table, function (Blueprint $t) use ($column, $refTable, $refCol, $final) {
            $fk = $t->foreign($column)->references($refCol)->on($refTable);

            switch ($final) {
                case 'CASCADE':
                    $fk->onDelete('cascade');
                    break;
                case 'RESTRICT':
                    $fk->onDelete('restrict');
                    break;
                case 'NO ACTION':
                    $fk->onDelete('no action');
                    break;
                case 'SET NULL':
                default:
                    $fk->nullOnDelete();
                    break;
            }
        });
    }

    public function up(): void
    {
        /**
         * Step 1: Drop existing FKs that point to users
         */
        // inventory_logs
        $this->dropForeignKeyIfExists('inventory_logs', 'created_by');
        $this->dropForeignKeyIfExists('inventory_logs', 'from_user_id');
        $this->dropForeignKeyIfExists('inventory_logs', 'to_user_id');

        // receipts
        $this->dropForeignKeyIfExists('inventory_stock_receipts', 'received_by');
        $this->dropForeignKeyIfExists('inventory_stock_receipt_lines', 'created_by');

        // assignments
        $this->dropForeignKeyIfExists('inventory_technician_item_assignments', 'technician_id');
        $this->dropForeignKeyIfExists('inventory_technician_item_assignments', 'assigned_by');

        // deployments
        $this->dropForeignKeyIfExists('inventory_item_deployments', 'technician_id');
        $this->dropForeignKeyIfExists('inventory_item_deployments', 'created_by');

        // movements
        $this->dropForeignKeyIfExists('inventory_movements', 'from_user_id');
        $this->dropForeignKeyIfExists('inventory_movements', 'to_user_id');
        $this->dropForeignKeyIfExists('inventory_movements', 'created_by');

        // item units
        $this->dropForeignKeyIfExists('inventory_item_units', 'assigned_to');

        // teams
        $this->dropForeignKeyIfExists('inventory_teams', 'created_by');
        $this->dropForeignKeyIfExists('inventory_team_members', 'technician_id');

        /**
         * Step 2: sanitize data (NULL orphan refs) BEFORE adding FKs
         * We also make nullable where we intend SET NULL
         */
        // logs
        $this->makeNullable('inventory_logs', 'created_by');
        $this->makeNullable('inventory_logs', 'from_user_id');
        $this->makeNullable('inventory_logs', 'to_user_id');

        $this->nullOrphans('inventory_logs', 'created_by', 'inventory_users');
        $this->nullOrphans('inventory_logs', 'from_user_id', 'inventory_users');
        $this->nullOrphans('inventory_logs', 'to_user_id', 'inventory_users');

        // receipts
        $this->makeNullable('inventory_stock_receipts', 'received_by'); // if you want strict, remove this
        $this->nullOrphans('inventory_stock_receipts', 'received_by', 'inventory_users');

        $this->makeNullable('inventory_stock_receipt_lines', 'created_by');
        $this->nullOrphans('inventory_stock_receipt_lines', 'created_by', 'inventory_users');

        // assignments
        $this->nullOrphans('inventory_technician_item_assignments', 'technician_id', 'inventory_users');
        $this->makeNullable('inventory_technician_item_assignments', 'assigned_by');
        $this->nullOrphans('inventory_technician_item_assignments', 'assigned_by', 'inventory_users');

        // deployments
        $this->nullOrphans('inventory_item_deployments', 'technician_id', 'inventory_users');
        $this->makeNullable('inventory_item_deployments', 'created_by');
        $this->nullOrphans('inventory_item_deployments', 'created_by', 'inventory_users');

        // movements
        $this->makeNullable('inventory_movements', 'from_user_id');
        $this->makeNullable('inventory_movements', 'to_user_id');
        $this->makeNullable('inventory_movements', 'created_by');

        $this->nullOrphans('inventory_movements', 'from_user_id', 'inventory_users');
        $this->nullOrphans('inventory_movements', 'to_user_id', 'inventory_users');
        $this->nullOrphans('inventory_movements', 'created_by', 'inventory_users');

        // item units
        $this->makeNullable('inventory_item_units', 'assigned_to');
        $this->nullOrphans('inventory_item_units', 'assigned_to', 'inventory_users');

        // teams
        $this->makeNullable('inventory_teams', 'created_by');
        $this->nullOrphans('inventory_teams', 'created_by', 'inventory_users');

        $this->nullOrphans('inventory_team_members', 'technician_id', 'inventory_users');

        /**
         * Step 3: Add new FKs -> inventory_users
         */
        // inventory_logs
        $this->addFk('inventory_logs', 'created_by', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_logs', 'from_user_id', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_logs', 'to_user_id', 'inventory_users', 'id', 'SET NULL');

        // receipts
        $this->addFk('inventory_stock_receipts', 'received_by', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_stock_receipt_lines', 'created_by', 'inventory_users', 'id', 'SET NULL');

        // assignments (technician_id should be required -> CASCADE)
        $this->addFk('inventory_technician_item_assignments', 'technician_id', 'inventory_users', 'id', 'CASCADE');
        $this->addFk('inventory_technician_item_assignments', 'assigned_by', 'inventory_users', 'id', 'SET NULL');

        // deployments
        $this->addFk('inventory_item_deployments', 'technician_id', 'inventory_users', 'id', 'CASCADE');
        $this->addFk('inventory_item_deployments', 'created_by', 'inventory_users', 'id', 'SET NULL');

        // movements
        $this->addFk('inventory_movements', 'from_user_id', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_movements', 'to_user_id', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_movements', 'created_by', 'inventory_users', 'id', 'SET NULL');

        // item units
        $this->addFk('inventory_item_units', 'assigned_to', 'inventory_users', 'id', 'SET NULL');

        // teams
        $this->addFk('inventory_teams', 'created_by', 'inventory_users', 'id', 'SET NULL');
        $this->addFk('inventory_team_members', 'technician_id', 'inventory_users', 'id', 'CASCADE');
    }

    public function down(): void
    {
        // no-op
    }
};
