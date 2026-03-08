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
    Schema::create('petty_payments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('hostel_id')->index();
        $table->string('reference')->nullable()->index();
        $table->decimal('amount', 14, 2);
        $table->date('date')->index();
        $table->unsignedBigInteger('recorded_by')->nullable()->index();
        $table->timestamps();

        $table->foreign('hostel_id')->references('id')->on('petty_hostels')->cascadeOnDelete();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_payments');
    }
};
