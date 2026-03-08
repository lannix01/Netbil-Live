<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('petty_bike_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('petty_bikes')->cascadeOnDelete();

            $table->date('service_date');
            $table->string('reference')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('transaction_cost', 12, 2)->default(0);

            $table->text('work_done')->nullable();
            $table->date('next_due_date')->nullable();

            $table->unsignedBigInteger('recorded_by')->nullable(); // petty user id
            $table->timestamps();

            $table->index(['bike_id', 'service_date']);
            $table->index('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_bike_services');
    }
};
