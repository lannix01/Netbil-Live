<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // admin | technician
            $table->string('inventory_role')->default('technician');

            $table->boolean('inventory_enabled')->default(true);
            $table->boolean('inventory_force_password_change')->default(false);

            // Optional link to inventory_departments
            $table->unsignedBigInteger('department_id')->nullable()->index();

            $table->rememberToken();
            $table->timestamps();
        });

        // Optional FK (safe even if you later rename)
        // If your departments table is named "inventory_departments" (it is, per migrations list)
        Schema::table('inventory_users', function (Blueprint $table) {
            $table->foreign('department_id')
                ->references('id')
                ->on('inventory_departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_users');
    }
};
