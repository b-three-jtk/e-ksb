<?php

namespace Database\Seeders;

use App\Enums\ConditionEnum;
use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Models\Financing;
use App\Models\FinancingProduct;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductType::factory()->count(5)->create();
        Product::factory()->count(20)->create();
        Financing::factory()->count(100)->create();
        // simulation
        $product = Product::create([
            'product_code' => 'PRD001',
            'product_name' => 'Laptop',
            'brand' => 'Dell',
            'specification' => 'Intel Core i7, 16GB RAM, 512GB SSD',
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'type_id' => 1,
        ]);

        FinancingProduct::create([
            'product_id' => $product->id,
            'condition' => ConditionEnum::NEW->value,
            'cost_price' => 15000000,
            'margin_amount' => 2000000,
            'qty' => 1,
            'request_description' => 'Pembelian laptop untuk keperluan kuliah',
        ]);

        Financing::create([
            'financing_transaction_code' => 'PM00000001',
            'financing_status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'is_wakalah' => true,
            'down_payment' => 3000000,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'user_id' => User::where('member_code', 'KSP002')->first()?->id,
            'created_at' => now(),
        ]);
    }
}
