<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Insert 1 allocation per existing spending that has batch_id
        DB::statement("
            INSERT INTO petty_spending_allocations (spending_id, batch_id, amount, transaction_cost, created_at, updated_at)
            SELECT s.id, s.batch_id, s.amount, COALESCE(s.transaction_cost,0), NOW(), NOW()
            FROM petty_spendings s
            WHERE s.batch_id IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM petty_spending_allocations a WHERE a.spending_id = s.id
              )
        ");
    }

    public function down(): void
    {
        // don’t delete allocations on rollback (it’s data)
        // intentionally left blank
    }
};
