<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_meal_daily_spendings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('respondent_id')->index();
            $table->date('spending_date')->index();
            $table->decimal('amount', 14, 2);
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('meal_payment_id')->nullable()->index();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['respondent_id', 'spending_date'], 'petty_meals_daily_unique');

            $table->foreign('respondent_id')->references('id')->on('petty_respondents')->cascadeOnDelete();
            $table->foreign('meal_payment_id')->references('id')->on('petty_meal_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_meal_daily_spendings');
    }
};
