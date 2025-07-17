<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class TransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
{
    for ($i = 1; $i <= 100; $i++) {
        DB::table('transactions')->insert([
            'user_id' => rand(1, 20),
            'invoice_number' => 'INV-10' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'amount' => rand(500, 3000),
            'method' => collect(['mpesa', 'cash', 'bank'])->random(),
            'status' => collect(['paid', 'pending', 'failed'])->random(),
            'mpesa_code' => Str::upper(Str::random(10)),
            'description' => 'Test invoice for invoice #' . $i,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
}