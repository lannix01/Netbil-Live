<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('system_audit_logs')) {
            return;
        }

        Schema::create('system_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('actor_role', 80)->nullable();
            $table->string('event', 120);
            $table->string('action', 160)->nullable();
            $table->string('description')->nullable();
            $table->string('method', 12)->nullable();
            $table->string('path')->nullable();
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['event']);
            $table->index(['action']);
            $table->index(['route_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_audit_logs');
    }
};
