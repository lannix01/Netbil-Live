<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paystackpayments', function (Blueprint $table) {
            $table->id();

            // who paid (optional if you want guests)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('reference')->unique();
            $table->string('channel')->nullable(); // card, bank, mpesa, etc.
            $table->string('currency')->nullable();
            $table->integer('amount')->default(0); // store in kobo
            $table->string('status')->default('pending'); // pending|success|failed
            $table->string('gateway')->default('paystack');

            $table->string('authorization_code')->nullable();
            $table->string('customer_email')->nullable();

            // useful for Netbil: attach what this payment is for
            $table->string('purpose')->nullable(); // e.g. topup / subscription / invoice
            $table->string('item_code')->nullable(); // plan code, invoice code, etc.

            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paystackpayments');
    }
};
