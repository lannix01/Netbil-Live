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
        Schema::create('interface_traffic', function (Blueprint $table) {
    $table->id();
    $table->string('interface_name');
    $table->bigInteger('rx_bps')->default(0);
    $table->bigInteger('tx_bps')->default(0);
    $table->string('status')->default('down');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interface_traffic');
    }
};