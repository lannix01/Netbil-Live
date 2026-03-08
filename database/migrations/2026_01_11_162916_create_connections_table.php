<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('connections', function (Blueprint $table) {
        $table->id();
        $table->string('mac_address')->index();
        $table->string('ip_address')->nullable();
        $table->foreignId('package_id')->constrained()->cascadeOnDelete();
        $table->string('username')->index();
        $table->timestamp('started_at');
        $table->timestamp('expires_at');
        $table->enum('status', ['active', 'expired', 'terminated'])->default('active');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
