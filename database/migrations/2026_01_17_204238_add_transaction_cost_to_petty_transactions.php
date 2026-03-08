<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_credits', function (Blueprint $table) {
            $table->decimal('transaction_cost', 12, 2)->default(0)->after('amount');
        });

        Schema::table('petty_spendings', function (Blueprint $table) {
            $table->decimal('transaction_cost', 12, 2)->default(0)->after('amount');
        });

        Schema::table('petty_payments', function (Blueprint $table) {
            $table->decimal('transaction_cost', 12, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('petty_credits', function (Blueprint $table) {
            $table->dropColumn('transaction_cost');
        });

        Schema::table('petty_spendings', function (Blueprint $table) {
            $table->dropColumn('transaction_cost');
        });

        Schema::table('petty_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_cost');
        });
    }
};