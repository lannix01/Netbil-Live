<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;

class DeviceSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        foreach (range(1, 15) as $i) {
            Device::create([
                'mac' => strtoupper($faker->bothify('##:##:##:##:##:##')),
                'ip' => $faker->ipv4,
                'active' => $faker->boolean(70),
            ]);
        }
    }
}
