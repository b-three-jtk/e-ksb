<?php

namespace Database\Seeders;

use App\Models\Heir;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HeirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Heir::factory()->count(20)->create();
    }
}
