<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('date');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->string('notes')->nullable()->after('receiver_phone');
        });
    }

    public function down(): void
    {
        Schema::table('petty_payments', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'receiver_phone', 'notes']);
        });
    }
};
