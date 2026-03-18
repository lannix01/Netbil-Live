<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_payments', 'is_overpay_application')) {
                $table->boolean('is_overpay_application')
                    ->default(false)
                    ->after('hostel_id')
                    ->index();
            }

            if (!Schema::hasColumn('petty_payments', 'overpay_source_payment_id')) {
                $table->unsignedBigInteger('overpay_source_payment_id')
                    ->nullable()
                    ->after('is_overpay_application')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            if (Schema::hasColumn('petty_payments', 'overpay_source_payment_id')) {
                $table->dropColumn('overpay_source_payment_id');
            }

            if (Schema::hasColumn('petty_payments', 'is_overpay_application')) {
                $table->dropColumn('is_overpay_application');
            }
        });
    }
};
