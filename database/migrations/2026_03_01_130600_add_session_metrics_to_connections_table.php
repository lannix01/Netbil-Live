<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('connections')) {
            return;
        }

        Schema::table('connections', function (Blueprint $table) {
            if (!Schema::hasColumn('connections', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('connections', 'start_bytes_in')) {
                $table->unsignedBigInteger('start_bytes_in')->default(0)->after('ended_at');
            }
            if (!Schema::hasColumn('connections', 'start_bytes_out')) {
                $table->unsignedBigInteger('start_bytes_out')->default(0)->after('start_bytes_in');
            }
            if (!Schema::hasColumn('connections', 'bytes_in')) {
                $table->unsignedBigInteger('bytes_in')->default(0)->after('start_bytes_out');
            }
            if (!Schema::hasColumn('connections', 'bytes_out')) {
                $table->unsignedBigInteger('bytes_out')->default(0)->after('bytes_in');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('connections')) {
            return;
        }

        Schema::table('connections', function (Blueprint $table) {
            $drops = [];
            foreach (['ended_at', 'start_bytes_in', 'start_bytes_out', 'bytes_in', 'bytes_out'] as $column) {
                if (Schema::hasColumn('connections', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
