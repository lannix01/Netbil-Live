<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function colType(string $table, string $col): ?string
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ", [$db, $table, $col]);

        return $row?->COLUMN_TYPE ?? null;
    }

    private function isUnsigned(?string $columnType): bool
    {
        return is_string($columnType) && str_contains(strtolower($columnType), 'unsigned');
    }

    public function up(): void
    {
        if (!Schema::hasTable('inventory_items')) {
            return;
        }

        // Determine whether inventory_items.id is unsigned
        $itemsIdType = $this->colType('inventory_items', 'id');
        $itemsIdUnsigned = $this->isUnsigned($itemsIdType);

        // Helper to align a column type to match inventory_items.id (bigint vs bigint unsigned)
        $alignItemId = function (string $table, string $col) use ($itemsIdUnsigned) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) return;

            // If column exists, we alter it to match signedness of inventory_items.id
            Schema::table($table, function (Blueprint $t) use ($col, $itemsIdUnsigned) {
                if ($itemsIdUnsigned) {
                    $t->unsignedBigInteger($col)->change();
                } else {
                    $t->bigInteger($col)->change();
                }
            });
        };

        // 1) Ensure item_id columns match inventory_items.id
        $alignItemId('inventory_item_units', 'item_id');
        $alignItemId('inventory_stock_receipt_lines', 'item_id');
        $alignItemId('inventory_technician_item_assignments', 'item_id');
        $alignItemId('inventory_item_deployments', 'item_id');
        $alignItemId('inventory_logs', 'item_id');
        $alignItemId('inventory_movement_lines', 'item_id');

        // 2) Ensure inventory_items.item_group_id matches inventory_item_groups.id (assume groups id is unsignedBigInteger)
        if (Schema::hasTable('inventory_item_groups') && Schema::hasColumn('inventory_items', 'item_group_id')) {
            // groups.id is normally unsigned bigint. Force item_group_id to unsigned bigint.
            Schema::table('inventory_items', function (Blueprint $t) {
                $t->unsignedBigInteger('item_group_id')->change();
            });
        }

        // 3) Add foreign keys (short names). Wrapped in try-catch to avoid breaking if data is inconsistent.
        //    You can later clean bad data and re-run by manually adding FKs.
        try {
            if (Schema::hasTable('inventory_item_groups') && Schema::hasColumn('inventory_items', 'item_group_id')) {
                Schema::table('inventory_items', function (Blueprint $t) {
                    $t->foreign('item_group_id', 'inv_items_group_fk')
                        ->references('id')->on('inventory_item_groups')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {
            // ignore to keep migrations moving
        }

        try {
            if (Schema::hasTable('inventory_item_units')) {
                Schema::table('inventory_item_units', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_unit_item_fk2')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inventory_stock_receipt_lines')) {
                Schema::table('inventory_stock_receipt_lines', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_rcpt_line_item_fk')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inventory_technician_item_assignments')) {
                Schema::table('inventory_technician_item_assignments', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_tia_item_fk')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inventory_item_deployments')) {
                Schema::table('inventory_item_deployments', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_dep_item_fk')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inventory_logs')) {
                Schema::table('inventory_logs', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_log_item_fk')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('inventory_movement_lines')) {
                Schema::table('inventory_movement_lines', function (Blueprint $t) {
                    $t->foreign('item_id', 'inv_movl_item_fk')
                        ->references('id')->on('inventory_items')
                        ->cascadeOnDelete();
                });
            }
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // Drop FKs if they exist
        $dropFk = function (string $table, string $fk) {
            if (!Schema::hasTable($table)) return;
            Schema::table($table, function (Blueprint $t) use ($fk) {
                try { $t->dropForeign($fk); } catch (\Throwable $e) {}
            });
        };

        $dropFk('inventory_items', 'inv_items_group_fk');
        $dropFk('inventory_item_units', 'inv_unit_item_fk2');
        $dropFk('inventory_stock_receipt_lines', 'inv_rcpt_line_item_fk');
        $dropFk('inventory_technician_item_assignments', 'inv_tia_item_fk');
        $dropFk('inventory_item_deployments', 'inv_dep_item_fk');
        $dropFk('inventory_logs', 'inv_log_item_fk');
        $dropFk('inventory_movement_lines', 'inv_movl_item_fk');
    }
};
