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
    Schema::table('messages', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('id');
        $table->text('text')->nullable()->after('phone');
        $table->string('sender')->default('system')->after('text');
        $table->timestamp('sent_at')->nullable()->after('sender');

        // Remove unused columns if no longer needed
        $table->dropColumn(['sender_id', 'receiver_id', 'message']);
    });
}

public function down()
{
    Schema::table('messages', function (Blueprint $table) {
        $table->dropColumn(['phone', 'text', 'sender', 'sent_at']);

        $table->unsignedBigInteger('sender_id');
        $table->unsignedBigInteger('receiver_id');
        $table->text('message');
    });
}

};
