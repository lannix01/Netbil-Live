<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('megapayments', function (Blueprint $table) {
            $table->id();

            // Your own unique reference for idempotency + linking to tokens/invoices
            $table->string('reference')->unique();

            // Customer + amount
            $table->string('msisdn')->index();
            $table->unsignedBigInteger('amount'); // KES integer

            // MegaPay identifiers
            $table->string('transaction_request_id')->nullable()->index(); // returned by /initiatestk
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();

            // Results
            $table->string('status')->default('pending')->index(); // pending|completed|failed|cancelled|timeout|expired
            $table->integer('response_code')->nullable();
            $table->string('response_description')->nullable();

            $table->string('mpesa_receipt')->nullable()->index();
            $table->string('transaction_id')->nullable()->index(); // SOFTPID...
            $table->string('transaction_date')->nullable();

            // Raw webhook payload for audit/debug
            $table->json('raw_webhook')->nullable();

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('megapayments');
    }
};
