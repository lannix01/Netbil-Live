<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_users', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_users', 'phone_no')) {
                $table->string('phone_no', 32)->nullable()->after('email');
            }

            if (!Schema::hasColumn('petty_users', 'login_sms_sent_at')) {
                $table->timestamp('login_sms_sent_at')->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_users', function (Blueprint $table) {
            if (Schema::hasColumn('petty_users', 'login_sms_sent_at')) {
                $table->dropColumn('login_sms_sent_at');
            }

            if (Schema::hasColumn('petty_users', 'phone_no')) {
                $table->dropColumn('phone_no');
            }
        });
    }
};
