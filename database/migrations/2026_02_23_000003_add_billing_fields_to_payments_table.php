<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('customer_id')->index();
            }

            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 30)->default('completed')->after('amount')->index();
            }

            if (!Schema::hasColumn('payments', 'reference')) {
                $table->string('reference')->nullable()->after('method')->index();
            }

            if (!Schema::hasColumn('payments', 'transaction_code')) {
                $table->string('transaction_code')->nullable()->after('transaction_id')->index();
            }

            if (!Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 8)->default('KES')->after('transaction_code');
            }

            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('currency');
            }

            if (!Schema::hasColumn('payments', 'meta')) {
                $table->json('meta')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = [
                'invoice_id',
                'status',
                'reference',
                'transaction_code',
                'currency',
                'paid_at',
                'meta',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

