<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // 'hotspot' | 'pppoe' | 'metered'
            $table->string('username')->nullable(); // hotspot username or pppoe username
            $table->string('password')->nullable(); // for PPPoE secret
            $table->string('mac_address')->nullable();
            $table->string('mk_profile')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending|active|expired|suspended
            $table->json('meta')->nullable(); // store raw mikrotik/session details
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
