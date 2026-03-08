<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pettycash_ledgers')) {
            return;
        }

        Schema::create('pettycash_ledgers', function (Blueprint $table) {
            $table->id();

            $table->date('date')->index();
            $table->string('reference')->nullable()->index();
            $table->string('category')->index(); // e.g. fuel, meals, tokens, service
            $table->text('description')->nullable();

            // money
            $table->decimal('amount', 14, 2)->default(0);

            // out / in
            $table->enum('direction', ['out', 'in'])->default('out')->index();

            // link back to source row (spending/service/etc)
            $table->string('source_type')->nullable()->index(); // e.g. spending, service
            $table->unsignedBigInteger('source_id')->nullable()->index();

            // optional audit
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'pc_ledgers_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pettycash_ledgers');
    }
};
