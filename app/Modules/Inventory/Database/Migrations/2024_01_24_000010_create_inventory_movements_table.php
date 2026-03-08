<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique(); // TRF-... / RTS-... / RFS-...
            $table->string('type');                // transfer | return_to_store | return_from_site
            $table->timestamp('movement_at')->useCurrent();

            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('site_code')->nullable();
            $table->string('site_name')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            //  short indexes
            $table->index(['type', 'movement_at'], 'inv_mov_type_time_idx');
            $table->index(['from_user_id', 'type'], 'inv_mov_from_type_idx');
            $table->index(['to_user_id', 'type'], 'inv_mov_to_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
