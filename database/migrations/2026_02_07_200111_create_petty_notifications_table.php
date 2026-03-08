<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_notifications', function (Blueprint $table) {
            $table->id();

            $table->string('module')->default('pettycash')->index(); // future-proof
            $table->string('type')->index(); // token_due_3, token_due_2, token_due_1, token_due_today, token_overdue
            $table->string('channel')->default('in_app')->index(); // in_app | email | sms (we’ll store in_app only here; send logs optionally)
            $table->string('title');
            $table->text('message')->nullable();

            $table->unsignedBigInteger('hostel_id')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->integer('days_to_due')->nullable()->index();

            $table->string('hostel_name')->nullable();
            $table->string('meter_no')->nullable();
            $table->string('phone_no')->nullable();

            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable()->index();

            $table->timestamp('sent_email_at')->nullable()->index();
            $table->timestamp('sent_sms_at')->nullable()->index();
            $table->text('send_error')->nullable();

            $table->string('dedupe_key')->unique(); // prevents duplicates

            $table->timestamps();

            $table->foreign('hostel_id')->references('id')->on('petty_hostels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_notifications');
    }
};
