<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('petty_hostel_pending_credits')) {
            return;
        }

        Schema::create('petty_hostel_pending_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hostel_id')->index();
            $table->decimal('amount', 14, 2);
            $table->string('reference', 120)->nullable()->index();
            $table->string('notes', 255)->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('sorted_by')->nullable()->index();
            $table->timestamp('sorted_at')->nullable();
            $table->timestamps();

            $table->foreign('hostel_id')
                ->references('id')
                ->on('petty_hostels')
                ->cascadeOnDelete();

            $table->foreign('payment_id')
                ->references('id')
                ->on('petty_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('petty_hostel_pending_credits')) {
            return;
        }

        Schema::table('petty_hostel_pending_credits', function (Blueprint $table) {
            $table->dropForeign(['hostel_id']);
            $table->dropForeign(['payment_id']);
        });

        Schema::dropIfExists('petty_hostel_pending_credits');
    }
};
