<?php

namespace Database\Seeders;

use App\Enums\FinancingReqStatus;
use App\Models\User;
use App\Enums\Condition;
use App\Models\Supplier;
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
        // simulation
        Financing::create([
            'transaction_code' => 'PM00000001',
            'product_name' => 'Laptop Dell Inspiron',
            'product_type' => 'Electronics',
            'brand' => 'Dell',
            'color' => 'Black',
            'condition' => Condition::NEW->value,
            'description' => 'A high-performance laptop suitable for work and gaming.',
            'cost_price' => 20000000,
            'qty' => 2,
            'margin' => 3000000,
            'tsaman_naqdy' => 17300000,
            'status' => FinancingReqStatus::ACTIVE_INSTALLMENTS->value,
            'isWakalah' => true,
            'down_payment' => 3000000,
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'user_id' => User::where('member_number', 'KSP002')->first()?->id,
            'created_at' => now(),
        ]);
    }
}
