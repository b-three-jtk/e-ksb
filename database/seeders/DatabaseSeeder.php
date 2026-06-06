<?php

namespace Database\Seeders;

use App\Models\JournalEntry;
use Database\Seeders\AccountSeeder;
use Database\Seeders\UserSeeder;
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
            UserSeeder::class,
            ProductTypeSeeder::class,
            AccountSeeder::class,
            SavingProductSeeder::class,
            MurabahaProductSeeder::class,
        ]);
    }
}
