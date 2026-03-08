<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Departments (ISP style)
        $departments = [
            ['name' => 'NOC', 'code' => 'NOC'],
            ['name' => 'Field Operations', 'code' => 'FIELD'],
            ['name' => 'Installations', 'code' => 'INSTALL'],
            ['name' => 'Support', 'code' => 'SUPPORT'],
            ['name' => 'Stores', 'code' => 'STORES'],
        ];

        foreach ($departments as $dept) {
            DB::table('inventory_departments')->updateOrInsert(
                ['name' => $dept['name']],
                ['code' => $dept['code'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // NOTE:
        // We are NOT creating users here, because every system handles users differently.
        // Just make sure your users table has:
        // - role: 'admin' or 'technician'
        // - department_id: points to inventory_departments.id
    }
}
