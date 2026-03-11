<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_meal_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spending_id')->nullable()->index();
            $table->unsignedBigInteger('respondent_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->date('range_from')->index();
            $table->date('range_to')->index();
            $table->unsignedInteger('days_count')->default(0);
            $table->decimal('amount', 14, 2);
            $table->decimal('transaction_cost', 14, 2)->default(0);
            $table->string('reference')->nullable()->index();
            $table->date('date')->index();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('spending_id')->references('id')->on('petty_spendings')->nullOnDelete();
            $table->foreign('respondent_id')->references('id')->on('petty_respondents')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('petty_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_meal_payments');
    }
};
