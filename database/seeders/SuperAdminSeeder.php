<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        SuperAdmin::updateOrCreate(
            ['email' => 'authenticator@example.com'],
            [
                'name' => 'Marcep Authenticator',
                'password' => Hash::make('password')
            ]
        );
    }
}
