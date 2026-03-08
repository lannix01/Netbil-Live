<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_user_id')
                ->constrained('petty_users')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['petty_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_api_tokens');
    }
};

