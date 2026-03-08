<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('status', 20)->default('SENT')->after('sender');
            $table->string('message_id')->nullable()->after('status');

            // optional but HIGHLY useful for debugging gateways
            $table->json('gateway_response')->nullable()->after('message_id');

            // optional if your sent_at is nullable
            // $table->timestamp('sent_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['status', 'message_id', 'gateway_response']);
        });
    }
};
