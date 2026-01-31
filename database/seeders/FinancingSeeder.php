<?php

namespace Database\Seeders;

use App\Models\Financing;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FinancingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Financing::factory()->count(100)->create();
    }
}
