<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('petty_spendings', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('petty_spendings', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable(false)->change();
        });
    }
};
