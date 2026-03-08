<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeteredUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('metered_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('metered_usages');
    }
}
