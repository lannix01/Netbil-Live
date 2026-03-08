<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE petty_users MODIFY role ENUM('admin','accountant','finance','viewer','customer_care') NOT NULL DEFAULT 'finance'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("UPDATE petty_users SET role = 'accountant' WHERE role IN ('finance', 'customer_care')");
        DB::statement("ALTER TABLE petty_users MODIFY role ENUM('admin','accountant','viewer') NOT NULL DEFAULT 'accountant'");
    }
};
