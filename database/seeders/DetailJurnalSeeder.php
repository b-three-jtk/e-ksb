<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DetailJurnalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed journal entries for all akun
        $akun = Akun::all();

        foreach ($akun as $a) {
            // Create a journal entry for each akun
            $a->detailJurnal()->create([
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
