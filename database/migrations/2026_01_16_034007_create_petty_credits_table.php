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
    Schema::create('petty_credits', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('batch_id')->index();
        $table->string('reference')->nullable()->index();
        $table->decimal('amount', 14, 2);
        $table->date('date')->index();
        $table->string('description')->nullable();

        $table->unsignedBigInteger('created_by')->nullable()->index();

        $table->timestamps();

        $table->foreign('batch_id')->references('id')->on('petty_batches')->cascadeOnDelete();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_credits');
    }
};
