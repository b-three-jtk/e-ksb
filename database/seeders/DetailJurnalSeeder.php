<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class DetailJurnalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $akun = Akun::all();

        foreach ($akun as $a) {
            $a->detailJurnal()->create([
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
