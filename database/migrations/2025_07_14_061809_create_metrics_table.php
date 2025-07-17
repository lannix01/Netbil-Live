<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
    $table->id();
    $table->float('ram_used');
    $table->integer('ram_percent');
    $table->integer('cpu_percent');
    $table->float('hdd_used');
    $table->integer('hdd_percent');
    $table->string('uptime');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};