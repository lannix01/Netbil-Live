<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMkFieldsToPackages extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('mk_profile')->nullable()->after('name'); // optional profile name
            $table->string('rate_limit')->nullable()->after('mk_profile'); // e.g. "10M/10M"
            $table->integer('duration_minutes')->nullable()->after('duration'); // override duration in minutes
            $table->boolean('auto_create_profile')->default(false)->after('rate_limit');
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['mk_profile','rate_limit','duration_minutes','auto_create_profile']);
        });
    }
}
