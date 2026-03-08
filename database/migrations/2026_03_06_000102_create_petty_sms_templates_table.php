<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_sms_templates');
    }
};
