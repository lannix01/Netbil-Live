<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('petty_meal_daily_spendings')) {
            try {
                DB::statement('ALTER TABLE `petty_meal_daily_spendings` DROP INDEX `petty_meals_daily_unique`');
            } catch (\Throwable $e) {
                // Index may already be missing; ignore.
            }

            try {
                DB::statement('ALTER TABLE `petty_meal_daily_spendings` MODIFY `respondent_id` BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Column shape may already match; ignore.
            }
        }

        if (Schema::hasTable('petty_meal_payments')) {
            try {
                DB::statement('ALTER TABLE `petty_meal_payments` MODIFY `respondent_id` BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Column shape may already match; ignore.
            }
        }
    }

    public function down(): void
    {
        // No-op: reverting to NOT NULL can fail when production data includes NULL respondent_id.
    }
};
