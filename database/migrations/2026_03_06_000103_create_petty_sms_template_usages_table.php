<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_sms_template_usages', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')
                ->on('petty_sms_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_sms_template_usages');
    }
};
