<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_api_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_api_tokens', 'device_id')) {
                $table->string('device_id', 120)->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('petty_api_tokens', 'device_platform')) {
                $table->string('device_platform', 40)->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('petty_api_tokens', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('expires_at')->index();
            }
            if (!Schema::hasColumn('petty_api_tokens', 'last_ip')) {
                $table->string('last_ip', 45)->nullable()->after('last_used_at');
            }
            if (!Schema::hasColumn('petty_api_tokens', 'last_user_agent')) {
                $table->string('last_user_agent', 255)->nullable()->after('last_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_api_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('petty_api_tokens', 'last_user_agent')) {
                $table->dropColumn('last_user_agent');
            }
            if (Schema::hasColumn('petty_api_tokens', 'last_ip')) {
                $table->dropColumn('last_ip');
            }
            if (Schema::hasColumn('petty_api_tokens', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
            if (Schema::hasColumn('petty_api_tokens', 'device_platform')) {
                $table->dropColumn('device_platform');
            }
            if (Schema::hasColumn('petty_api_tokens', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
    }
};
