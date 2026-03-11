<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_meal_daily_respondents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meal_daily_spending_id')->index();
            $table->unsignedBigInteger('respondent_id')->index();
            $table->timestamps();

            $table->unique(['meal_daily_spending_id', 'respondent_id'], 'petty_meal_daily_respondents_unique');

            $table->foreign('meal_daily_spending_id')
                ->references('id')
                ->on('petty_meal_daily_spendings')
                ->cascadeOnDelete();

            $table->foreign('respondent_id')
                ->references('id')
                ->on('petty_respondents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_meal_daily_respondents');
    }
};
