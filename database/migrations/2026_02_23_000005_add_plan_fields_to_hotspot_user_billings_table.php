<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_user_billings', function (Blueprint $table) {
            if (!Schema::hasColumn('hotspot_user_billings', 'package_id')) {
                $table->unsignedBigInteger('package_id')->nullable()->after('customer_id')->index();
            }

            if (!Schema::hasColumn('hotspot_user_billings', 'notify_customer')) {
                $table->boolean('notify_customer')->default(false)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_user_billings', function (Blueprint $table) {
            $columns = ['package_id', 'notify_customer'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('hotspot_user_billings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

