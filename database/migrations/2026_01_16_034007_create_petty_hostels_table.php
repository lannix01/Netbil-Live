<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('petty_hostels', function (Blueprint $table) {
        $table->id();
        $table->string('hostel_name');
        $table->string('meter_no')->nullable()->index();
        $table->string('phone_no')->nullable()->index();
        $table->unsignedInteger('no_of_routers')->default(0);
        $table->enum('stake', ['monthly', 'semester'])->default('monthly');
        $table->decimal('amount_due', 14, 2)->default(0);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_hostels');
    }
};
