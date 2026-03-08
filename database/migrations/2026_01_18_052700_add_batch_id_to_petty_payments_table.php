<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('hostel_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
