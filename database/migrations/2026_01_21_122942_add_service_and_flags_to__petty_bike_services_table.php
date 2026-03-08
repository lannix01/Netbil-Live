<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add columns ONLY if missing (prevents duplicate column errors)
        Schema::table('petty_bikes', function (Blueprint $table) {

            if (!Schema::hasColumn('petty_bikes', 'last_service_date')) {
                $table->date('last_service_date')->nullable()->after('plate_no');
            }

            if (!Schema::hasColumn('petty_bikes', 'next_service_due_date')) {
                $table->date('next_service_due_date')->nullable()->after('last_service_date');
            }

            if (!Schema::hasColumn('petty_bikes', 'is_unroadworthy')) {
                $table->boolean('is_unroadworthy')->default(false)->after('next_service_due_date');
            }

            if (!Schema::hasColumn('petty_bikes', 'unroadworthy_notes')) {
                $table->text('unroadworthy_notes')->nullable()->after('is_unroadworthy');
            }

            if (!Schema::hasColumn('petty_bikes', 'unroadworthy_at')) {
                $table->timestamp('unroadworthy_at')->nullable()->after('unroadworthy_notes');
            }
        });
    }

    public function down(): void
    {
        // Drop ONLY if exists
        Schema::table('petty_bikes', function (Blueprint $table) {
            if (Schema::hasColumn('petty_bikes', 'unroadworthy_at')) {
                $table->dropColumn('unroadworthy_at');
            }
            if (Schema::hasColumn('petty_bikes', 'unroadworthy_notes')) {
                $table->dropColumn('unroadworthy_notes');
            }
            if (Schema::hasColumn('petty_bikes', 'is_unroadworthy')) {
                $table->dropColumn('is_unroadworthy');
            }
            if (Schema::hasColumn('petty_bikes', 'next_service_due_date')) {
                $table->dropColumn('next_service_due_date');
            }
            if (Schema::hasColumn('petty_bikes', 'last_service_date')) {
                $table->dropColumn('last_service_date');
            }
        });
    }
};
