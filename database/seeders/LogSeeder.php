<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Log;

class LogSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        foreach (range(1, 30) as $i) {
            Log::create([
                'username' => $faker->userName,
                'info' => $faker->sentence,
            ]);
        }
    }
}
