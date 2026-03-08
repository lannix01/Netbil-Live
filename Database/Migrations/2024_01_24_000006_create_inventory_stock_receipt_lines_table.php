<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stock_receipt_lines', function (Blueprint $table) {
            $table->id();

            // Keep the receipt FK (this one should work since receipts table was just created)
            $table->foreignId('stock_receipt_id')->constrained('inventory_stock_receipts')->cascadeOnDelete();

            //  item_id as plain column for now (FK added later once we confirm inventory_items.id type)
            $table->unsignedBigInteger('item_id');

            $table->unsignedInteger('qty_received');
            $table->unsignedInteger('unit_cost')->nullable();

            $table->timestamps();

            //  short index name
            $table->index(['stock_receipt_id', 'item_id'], 'inv_rcpt_line_rcpt_item_idx');
            $table->index(['item_id'], 'inv_rcpt_line_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_receipt_lines');
    }
};
