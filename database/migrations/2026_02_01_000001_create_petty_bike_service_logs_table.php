<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_bike_service_logs', function (Blueprint $table) {
            $table->id();

            // the service being acted on (nullable because after delete it won't exist)
            $table->unsignedBigInteger('bike_service_id')->nullable();
            $table->unsignedBigInteger('bike_id')->nullable();

            // action metadata
            $table->string('action', 40); // e.g. deleted
            $table->unsignedBigInteger('performed_by')->nullable();

            // snapshot of what was deleted
            $table->json('payload')->nullable();

            // request metadata (useful in audits)
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Optional indexes
            $table->index(['action', 'created_at']);
            $table->index(['bike_id', 'created_at']);
            $table->index(['bike_service_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_bike_service_logs');
    }
};
