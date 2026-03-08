<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreatePackagesTable extends Migration
{
    public function up()
{
    Schema::create('packages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 10, 2);

    // hotspot logic
    $table->string('mikrotik_profile')->unique();
    $table->string('speed'); // e.g 2M/2M
    $table->integer('duration'); // hours
    $table->bigInteger('data_limit')->nullable(); // bytes

    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
