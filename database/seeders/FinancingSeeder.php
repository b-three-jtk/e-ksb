<?php

namespace Database\Seeders;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\Collateral;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\InstallmentPaymentSchedule;
use App\Models\InstallmentPaymentTransaction;
use App\Models\Member;
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
        FinancingItem::factory()->count(100)->create();

        // SIMULATION - Complete Financing with Installment, Schedule, and Transaction
        $member = Member::with('user')->whereHas('user', function ($query) {
            $query->where('name', 'Anggota');
        })->first();

        $financing = Financing::create([
            'financing_transaction_code' => 'PM00000001',
            'financing_status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'is_wakalah' => true,
            'down_payment' => 3000000,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'akad_date' => now()->subMonths(3)->format('Y-m-d'),
            'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'member_id' => $member->id,
        ]);

        $productType = ProductType::create([
            'product_type_name' => 'Motor',
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'PT. Motorindo',
        ]);

        Collateral::create([
            'collateral_type' => 'BPKB',
            'estimated_market_value' => 4000000,
            'owner_name' => $member->user->name,
            'collateral_location' => 'Bandung',
            'financing_id' => $financing->id,
        ]);

        $financingItem = FinancingItem::create([
            'name' => 'Motor Yamaha NMAX',
            'cost_price' => 3500000,
            'margin_amount' => 280000,
            'financing_id' => $financing->id,
            'request_description' => 'Pembelian motor untuk kebutuhan sehari-hari',
            'qty' => 1,
            'condition' => 'Baru',
            'product_type_id' => $productType->id,
            'supplier_id' => $supplier->id,
        ]);

        // Create Installment
        $tenor = 12; // 12 bulan
        $installment = Installment::create([
            'tenor' => $tenor,
            'financing_id' => $financing->id,
        ]);

        // Create Installment Payment Schedules
        $monthlyPayment = ($financing->down_payment + $financingItem->margin_amount) / $tenor; 

        for ($i = 1; $i <= $tenor; $i++) {
            $dueDate = now()->addMonths($i)->format('Y-m-d');

            $schedule = InstallmentPaymentSchedule::create([
                'installment_id' => $installment->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'installment_schedule_status' => $i <= 2 ? InstallmentPaymentScheduleStatusEnum::PAID->value : InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);

            // Create Payment Transaction for paid schedules
            if ($i <= 2) {
                InstallmentPaymentTransaction::create([
                    'installment_trans_code' => 'LP' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'principal_paid' => $monthlyPayment * 0.8,
                    'margin_paid' => $monthlyPayment * 0.2,
                    'installment_payment_method' => PaymentMethodsEnum::CASH->value,
                    'is_early_repayment' => false,
                    'installment_payment_schedule_id' => $schedule->id,
                    'payment_date' => $dueDate,
                    'updated_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
                ]);
            }
        }
    }
}
