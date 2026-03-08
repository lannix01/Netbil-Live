<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('id')->constrained('inventory_departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('department_id'); // admin | technician
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::dropIfExists('inventory_departments');
    }
};
