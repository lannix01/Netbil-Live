<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'invoice_status')) {
                $table->string('invoice_status', 30)->default('unpaid')->after('status')->index();
            }

            if (!Schema::hasColumn('invoices', 'currency')) {
                $table->string('currency', 8)->default('KES')->after('amount');
            }

            if (!Schema::hasColumn('invoices', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->after('invoice_number');
            }

            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('issued_at');
            }

            if (!Schema::hasColumn('invoices', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('amount');
            }

            if (!Schema::hasColumn('invoices', 'tax_percent')) {
                $table->decimal('tax_percent', 8, 2)->default(0)->after('subtotal_amount');
            }

            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_percent');
            }

            if (!Schema::hasColumn('invoices', 'penalty_percent')) {
                $table->decimal('penalty_percent', 8, 2)->default(0)->after('tax_amount');
            }

            if (!Schema::hasColumn('invoices', 'penalty_amount')) {
                $table->decimal('penalty_amount', 12, 2)->default(0)->after('penalty_percent');
            }

            if (!Schema::hasColumn('invoices', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('penalty_amount');
            }

            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            }

            if (!Schema::hasColumn('invoices', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');
            }

            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('balance_amount');
            }

            if (!Schema::hasColumn('invoices', 'public_token')) {
                $table->string('public_token', 80)->nullable()->after('notes')->unique();
            }

            if (!Schema::hasColumn('invoices', 'public_token_expires_at')) {
                $table->timestamp('public_token_expires_at')->nullable()->after('public_token');
            }

            if (!Schema::hasColumn('invoices', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable()->after('public_token_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = [
                'invoice_status',
                'currency',
                'issued_at',
                'due_date',
                'subtotal_amount',
                'tax_percent',
                'tax_amount',
                'penalty_percent',
                'penalty_amount',
                'total_amount',
                'paid_amount',
                'balance_amount',
                'notes',
                'public_token',
                'public_token_expires_at',
                'last_reminder_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

