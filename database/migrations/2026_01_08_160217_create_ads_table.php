<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('title');
            $table->text('content')->nullable(); // text/html
            $table->string('image_path')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();

            // Scheduling
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Display logic
            $table->unsignedInteger('priority')->default(0); // higher = shows first
            $table->unsignedInteger('views')->default(0);

            // Audit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
