<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('petty_user_id')->unique();
            $table->json('permissions')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('petty_user_id')
                ->references('id')
                ->on('petty_users')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('petty_users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('petty_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_user_permissions');
    }
};
