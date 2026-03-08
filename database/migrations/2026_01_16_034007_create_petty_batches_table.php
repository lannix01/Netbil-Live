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
    Schema::create('petty_batches', function (Blueprint $table) {
        $table->id();

        $table->string('batch_no')->unique(); // PC-2026-0001
        $table->decimal('opening_balance', 14, 2)->default(0);
        $table->decimal('credited_amount', 14, 2)->default(0);

        $table->unsignedBigInteger('created_by')->nullable()->index();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_batches');
    }
};
