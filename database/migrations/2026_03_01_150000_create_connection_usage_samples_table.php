<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('connections') || Schema::hasTable('connection_usage_samples')) {
            return;
        }

        Schema::create('connection_usage_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('connections')->cascadeOnDelete();
            $table->timestamp('recorded_at')->index();
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->unsignedBigInteger('bytes_in')->default(0);
            $table->unsignedBigInteger('bytes_out')->default(0);
            $table->string('source', 32)->default('snapshot');
            $table->timestamps();

            $table->index(['connection_id', 'recorded_at'], 'connection_usage_samples_conn_recorded_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('connection_usage_samples')) {
            return;
        }

        Schema::dropIfExists('connection_usage_samples');
    }
};
