<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable()->after('customer_id')->index();
            }

            if (!Schema::hasColumn('services', 'mac_address')) {
                $table->string('mac_address')->nullable()->after('status');
            }

            if (!Schema::hasColumn('services', 'rate_per_gb')) {
                $table->decimal('rate_per_gb', 10, 2)->nullable()->after('mac_address');
            }

            if (!Schema::hasColumn('services', 'billing_unit')) {
                $table->string('billing_unit', 20)->default('gb')->after('rate_per_gb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = ['package_id', 'mac_address', 'rate_per_gb', 'billing_unit'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

