<?php

namespace Database\Seeders;

use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\Member;
use App\Models\User;
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
            SavingProductSeeder::class,
            MurabahaProductSeeder::class,
        ]);

        // Seed demo members
        $members = Member::factory(5)->create();

        // Seed demo financing with complete data
        foreach ($members->take(1) as $member) {
            $financing = Financing::factory()
                ->activeInstallments()
                ->create([
                    'member_id' => $member->id,
                    'updated_by' => User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first()?->id ?? User::first()?->id,
                    'cost_price' => 10000000,
                    'margin_amount' => 2000000,
                    'down_payment' => 2000000,
                ]);

            // Create financing item
            FinancingItem::factory()
                ->create([
                    'financing_id' => $financing->id,
                    'name' => 'Motor Yamaha NMAX 155',
                    'specification' => 'Motor Nmax 155cc Tahun 2024, Kondisi Baru, Warna Merah',
                    'qty' => 1,
                    'price_per_unit' => 10000000,
                ]);

            // Create installment schedule
            Installment::factory()
                ->tenor12()
                ->create([
                    'financing_id' => $financing->id,
                    'due_day' => 5,
                ]);
        }
    }
}
