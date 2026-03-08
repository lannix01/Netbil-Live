<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('megapayments', function (Blueprint $table) {
            if (!Schema::hasColumn('megapayments', 'purpose')) {
                $table->string('purpose')->nullable()->after('reference')->index();
            }
            if (!Schema::hasColumn('megapayments', 'channel')) {
                $table->string('channel')->nullable()->after('purpose')->index();
            }

            // Polymorphic link to what is being paid
            if (!Schema::hasColumn('megapayments', 'payable_type')) {
                $table->string('payable_type')->nullable()->after('channel')->index();
            }
            if (!Schema::hasColumn('megapayments', 'payable_id')) {
                $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type')->index();
            }

            // Optional relations
            if (!Schema::hasColumn('megapayments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('payable_id')->index();
            }
            if (!Schema::hasColumn('megapayments', 'initiated_by')) {
                $table->unsignedBigInteger('initiated_by')->nullable()->after('customer_id')->index();
            }

            if (!Schema::hasColumn('megapayments', 'meta')) {
                $table->json('meta')->nullable()->after('raw_webhook');
            }
        });
    }

    public function down(): void
    {
        Schema::table('megapayments', function (Blueprint $table) {
            foreach (['purpose','channel','payable_type','payable_id','customer_id','initiated_by','meta'] as $col) {
                if (Schema::hasColumn('megapayments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
