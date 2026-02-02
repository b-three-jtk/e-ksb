<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\SupplierSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
            WorkUnitSeeder::class,
            UserSeeder::class,
            SupplierSeeder::class,
            FinancialSeeder::class,
            AccountSeeder::class,
            FinancingSeeder::class,
            LoanSeeder::class,
            HeirSeeder::class,
            SavingAccountSeeder::class,
        ]);
    }
}
