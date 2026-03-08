<?php

namespace App\Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\Inventory\Models\InventoryUser;

class InventoryAuthSeeder extends Seeder
{
    public function run(): void
    {
        // Default password for all seeded accounts:
        $defaultPassword = 'ChangeMe123!';

        // Admins (3)
        $admins = [
            ['name' => 'Marcep Admin', 'email' => 'marcep@skybrix.co.ke'],
            ['name' => 'Bravin Admin', 'email' => 'bravin@skybrix.co.ke'],
            ['name' => 'Steve Admin', 'email' => 'steve@skybrix.co.ke'],
        ];

        foreach ($admins as $admin) {
            InventoryUser::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($defaultPassword),
                    'inventory_role' => 'admin',
                    'inventory_enabled' => true,
                    'inventory_force_password_change' => true,
                    'department_id' => null,
                ]
            );
        }

        // Technicians (9)
        $technicians = [
            ['name' => 'Okodoi Technician', 'email' => 'okodoi@skybrix.co.ke'],
            ['name' => 'Omondi Technician', 'email' => 'omondi@skybrix.co.ke'],
            ['name' => 'Alela Technician', 'email' => 'alela@skybrix.co.ke'],
            ['name' => 'Danson Technician', 'email' => 'danson@skybrix.co.ke'],
            ['name' => 'Gadi Technician', 'email' => 'gadi@skybrix.co.ke'],
            ['name' => 'Mwangi Technician', 'email' => 'mwangi@skybrix.co.ke'],
            ['name' => 'Kisia Technician', 'email' => 'kisia@skybrix.co.ke'],
            ['name' => 'Mazali Technician', 'email' => 'mazali@skybrix.co.ke'],
            ['name' => 'Mavin Technician', 'email' => 'mavin@skybrix.co.ke'],
        ];

        foreach ($technicians as $technician) {
            InventoryUser::updateOrCreate(
                ['email' => $technician['email']],
                [
                    'name' => $technician['name'],
                    'password' => Hash::make($defaultPassword),
                    'inventory_role' => 'technician',
                    'inventory_enabled' => true,
                    'inventory_force_password_change' => true,
                    'department_id' => null,
                ]
            );
        }
    }
}
