<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ads')) {
            return;
        }

        if (!Schema::hasColumn('ads', 'media_type')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->string('media_type', 16)->nullable()->after('content');
            });
        }

        if (!Schema::hasColumn('ads', 'media_url')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->string('media_url')->nullable()->after('media_type');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ads')) {
            return;
        }

        $dropColumns = [];
        if (Schema::hasColumn('ads', 'media_url')) {
            $dropColumns[] = 'media_url';
        }
        if (Schema::hasColumn('ads', 'media_type')) {
            $dropColumns[] = 'media_type';
        }

        if (!empty($dropColumns)) {
            Schema::table('ads', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }
};
