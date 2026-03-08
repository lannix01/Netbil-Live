<?php

namespace App\Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InventorySeeder::class,
        ]);
    }
}
