<?php

namespace Database\Seeders;

use App\Models\DetailJurnal;
use Database\Seeders\AkunSeeder;
use Database\Seeders\PenggunaSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PenggunaSeeder::class,
            JenisBarangSeeder::class,
            AkunSeeder::class,
            SavingProductSeeder::class,
            MurabahaProductSeeder::class,
            GlobalSettingSeeder::class,
        ]);
    }
}
