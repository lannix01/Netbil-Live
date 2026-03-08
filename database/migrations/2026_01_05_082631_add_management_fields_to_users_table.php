<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('user')->after('phone');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('can_login')->default(true)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('can_login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'is_active',
                'can_login',
                'last_login_at',
            ]);
        });
    }
};
