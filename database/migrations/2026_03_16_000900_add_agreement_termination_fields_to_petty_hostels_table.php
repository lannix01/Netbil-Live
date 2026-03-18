<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_hostels', 'agreement_terminated_at')) {
                $table->timestamp('agreement_terminated_at')->nullable()->after('agreement_label')->index();
            }

            if (!Schema::hasColumn('petty_hostels', 'agreement_termination_reason')) {
                $table->string('agreement_termination_reason', 120)->nullable()->after('agreement_terminated_at');
            }

            if (!Schema::hasColumn('petty_hostels', 'agreement_termination_notes')) {
                $table->string('agreement_termination_notes', 255)->nullable()->after('agreement_termination_reason');
            }

            if (!Schema::hasColumn('petty_hostels', 'agreement_transfer_hostel_id')) {
                $table->unsignedBigInteger('agreement_transfer_hostel_id')->nullable()->after('agreement_termination_notes');
                $table->foreign('agreement_transfer_hostel_id')
                    ->references('id')
                    ->on('petty_hostels')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_hostels', function (Blueprint $table) {
            if (Schema::hasColumn('petty_hostels', 'agreement_transfer_hostel_id')) {
                $table->dropForeign(['agreement_transfer_hostel_id']);
                $table->dropColumn('agreement_transfer_hostel_id');
            }

            if (Schema::hasColumn('petty_hostels', 'agreement_termination_notes')) {
                $table->dropColumn('agreement_termination_notes');
            }

            if (Schema::hasColumn('petty_hostels', 'agreement_termination_reason')) {
                $table->dropColumn('agreement_termination_reason');
            }

            if (Schema::hasColumn('petty_hostels', 'agreement_terminated_at')) {
                $table->dropIndex(['agreement_terminated_at']);
                $table->dropColumn('agreement_terminated_at');
            }
        });
    }
};
