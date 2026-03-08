<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_user_billings', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rate_per_gb', 10, 2)->default(0);
            $table->string('currency', 8)->default('KES');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_user_billings');
    }
};

