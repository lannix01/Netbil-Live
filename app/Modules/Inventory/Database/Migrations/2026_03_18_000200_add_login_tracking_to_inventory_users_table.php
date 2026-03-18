<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_users', 'phone_no')) {
                $table->string('phone_no', 32)->nullable()->after('email');
            }

            if (!Schema::hasColumn('inventory_users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }

            if (!Schema::hasColumn('inventory_users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (!Schema::hasColumn('inventory_users', 'last_login_user_agent')) {
                $table->string('last_login_user_agent', 255)->nullable()->after('last_login_ip');
            }

            if (!Schema::hasColumn('inventory_users', 'login_sms_sent_at')) {
                $table->timestamp('login_sms_sent_at')->nullable()->after('last_login_user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_users', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_users', 'login_sms_sent_at')) {
                $table->dropColumn('login_sms_sent_at');
            }

            if (Schema::hasColumn('inventory_users', 'last_login_user_agent')) {
                $table->dropColumn('last_login_user_agent');
            }

            if (Schema::hasColumn('inventory_users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }

            if (Schema::hasColumn('inventory_users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }

            if (Schema::hasColumn('inventory_users', 'phone_no')) {
                $table->dropColumn('phone_no');
            }
        });
    }
};
