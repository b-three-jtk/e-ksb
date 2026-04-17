<?php

namespace Database\Seeders;

use Database\Seeders\InstallmentSeeder;
use Database\Seeders\SupplierSeeder;
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
        // User::factory(10)->create();
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            SupplierSeeder::class,
            FinancialSeeder::class,
            AccountSeeder::class,
            FinancingSeeder::class,
            InstallmentSeeder::class,
            HeirSeeder::class,
            SavingAccountSeeder::class,
        ]);
    }
}
