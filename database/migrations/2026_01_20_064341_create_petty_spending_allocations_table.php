<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('petty_spending_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('spending_id')->constrained('petty_spendings')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('petty_batches')->cascadeOnDelete();

            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('transaction_cost', 14, 2)->default(0);

            $table->timestamps();

            $table->index(['batch_id', 'spending_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_spending_allocations');
    }
};
