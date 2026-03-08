<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add temporary column (use LONGTEXT for max compatibility on MariaDB;
        // Laravel will still cast it to array if it contains valid JSON string)
        if (!Schema::hasColumn('megapayments', 'raw_webhook_json')) {
            Schema::table('megapayments', function (Blueprint $table) {
                $table->longText('raw_webhook_json')->nullable()->after('raw_webhook');
            });
        }

        // Normalize existing data row-by-row (MariaDB-safe)
        DB::table('megapayments')
            ->select('id', 'raw_webhook')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $raw = $row->raw_webhook;

                    if ($raw === null || $raw === '') {
                        DB::table('megapayments')
                            ->where('id', $row->id)
                            ->update(['raw_webhook_json' => null]);
                        continue;
                    }

                    // raw might already be JSON, or a quoted JSON string, or escaped JSON.
                    $decoded = json_decode($raw, true);

                    if (!is_array($decoded)) {
                        // Try trimming wrapping quotes
                        $trimmed = trim($raw);

                        // If it looks like a quoted JSON string:  " {...} "
                        if (strlen($trimmed) >= 2 && $trimmed[0] === '"' && substr($trimmed, -1) === '"') {
                            $trimmed = substr($trimmed, 1, -1);
                        }

                        // Try unescaping common sequences
                        $unescaped = stripcslashes($trimmed);
                        $decoded = json_decode($unescaped, true);

                        if (!is_array($decoded)) {
                            // Last attempt: maybe it's already a plain object string but with weird escapes
                            $decoded = json_decode(str_replace('\"', '"', $trimmed), true);
                        }
                    }

                    // If still not valid JSON, store null (or store original string if you prefer)
                    if (!is_array($decoded)) {
                        DB::table('megapayments')
                            ->where('id', $row->id)
                            ->update(['raw_webhook_json' => null]);
                        continue;
                    }

                    DB::table('megapayments')
                        ->where('id', $row->id)
                        ->update(['raw_webhook_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE)]);
                }
            });

        // Swap columns
        if (Schema::hasColumn('megapayments', 'raw_webhook')) {
            Schema::table('megapayments', function (Blueprint $table) {
                $table->dropColumn('raw_webhook');
            });
        }

        Schema::table('megapayments', function (Blueprint $table) {
            $table->renameColumn('raw_webhook_json', 'raw_webhook');
        });
    }

    public function down(): void
    {
        // Reverse is optional; keep it simple and safe.
        // Recreate old raw_webhook_text and copy back JSON string.
        if (!Schema::hasColumn('megapayments', 'raw_webhook_text')) {
            Schema::table('megapayments', function (Blueprint $table) {
                $table->longText('raw_webhook_text')->nullable()->after('raw_webhook');
            });
        }

        DB::table('megapayments')
            ->select('id', 'raw_webhook')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $raw = $row->raw_webhook;
                    // Ensure it's stored as text JSON
                    if (is_array($raw)) {
                        $raw = json_encode($raw, JSON_UNESCAPED_UNICODE);
                    }
                    DB::table('megapayments')
                        ->where('id', $row->id)
                        ->update(['raw_webhook_text' => $raw]);
                }
            });

        if (Schema::hasColumn('megapayments', 'raw_webhook')) {
            Schema::table('megapayments', function (Blueprint $table) {
                $table->dropColumn('raw_webhook');
            });
        }

        Schema::table('megapayments', function (Blueprint $table) {
            $table->renameColumn('raw_webhook_text', 'raw_webhook');
        });
    }
};
