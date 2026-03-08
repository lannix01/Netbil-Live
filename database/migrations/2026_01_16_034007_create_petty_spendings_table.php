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
    Schema::create('petty_spendings', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('batch_id')->index();

        $table->string('type')->index();       // bike, meal, token, other
        $table->string('sub_type')->nullable()->index(); // fuel, maintenance, lunch, etc

        $table->string('reference')->nullable()->index();
        $table->decimal('amount', 14, 2);
        $table->date('date')->index();

        $table->unsignedBigInteger('respondent_id')->nullable()->index();
        $table->string('description')->nullable();

        // points to bike_id or hostel_id depending on type
        $table->unsignedBigInteger('related_id')->nullable()->index();

        // bike maintenance extra notes (only relevant for maintenance)
        $table->text('particulars')->nullable();

        $table->timestamps();

        $table->foreign('batch_id')->references('id')->on('petty_batches')->cascadeOnDelete();
        $table->foreign('respondent_id')->references('id')->on('petty_respondents')->nullOnDelete();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_spendings');
    }
};
