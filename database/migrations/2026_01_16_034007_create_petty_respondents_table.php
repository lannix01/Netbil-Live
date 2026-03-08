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
    Schema::create('petty_respondents', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('phone')->nullable()->index();
        $table->string('category')->nullable()->index();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_respondents');
    }
};
