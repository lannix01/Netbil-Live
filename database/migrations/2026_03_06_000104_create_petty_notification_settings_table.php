<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('sms_gateway')->default('advanta');
            $table->boolean('sms_enabled')->default(true);
            $table->decimal('low_balance_threshold', 14, 2)->default(0);
            $table->decimal('low_credit_threshold', 14, 2)->default(0);
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_notification_settings');
    }
};
