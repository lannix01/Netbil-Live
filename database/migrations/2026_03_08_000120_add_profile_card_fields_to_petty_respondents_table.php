<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_respondents', 'profile_title')) {
                $table->string('profile_title', 120)->nullable()->after('category');
            }
            if (!Schema::hasColumn('petty_respondents', 'profile_email')) {
                $table->string('profile_email', 160)->nullable()->after('profile_title');
            }
            if (!Schema::hasColumn('petty_respondents', 'profile_location')) {
                $table->string('profile_location', 180)->nullable()->after('profile_email');
            }
            if (!Schema::hasColumn('petty_respondents', 'profile_notes')) {
                $table->text('profile_notes')->nullable()->after('profile_location');
            }
            if (!Schema::hasColumn('petty_respondents', 'profile_photo_path')) {
                $table->string('profile_photo_path', 255)->nullable()->after('profile_notes');
            }

            if (!Schema::hasColumn('petty_respondents', 'card_public_token')) {
                $table->string('card_public_token', 100)->nullable()->after('profile_photo_path')->index();
            }
            if (!Schema::hasColumn('petty_respondents', 'card_file_path')) {
                $table->string('card_file_path', 255)->nullable()->after('card_public_token');
            }
            if (!Schema::hasColumn('petty_respondents', 'card_generated_at')) {
                $table->timestamp('card_generated_at')->nullable()->after('card_file_path');
            }
            if (!Schema::hasColumn('petty_respondents', 'card_sms_sent_at')) {
                $table->timestamp('card_sms_sent_at')->nullable()->after('card_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('petty_respondents', function (Blueprint $table) {
            if (Schema::hasColumn('petty_respondents', 'card_public_token')) {
                try {
                    $table->dropIndex('petty_respondents_card_public_token_index');
                } catch (\Throwable $e) {
                    // Ignore if index is missing or has a different name.
                }
            }

            $drop = [
                'profile_title',
                'profile_email',
                'profile_location',
                'profile_notes',
                'profile_photo_path',
                'card_public_token',
                'card_file_path',
                'card_generated_at',
                'card_sms_sent_at',
            ];

            $existing = [];
            foreach ($drop as $column) {
                if (Schema::hasColumn('petty_respondents', $column)) {
                    $existing[] = $column;
                }
            }

            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
