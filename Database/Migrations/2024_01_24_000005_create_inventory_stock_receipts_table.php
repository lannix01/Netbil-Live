<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stock_receipts', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->date('received_date');
            $table->string('supplier_name')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_receipts');
    }
};
