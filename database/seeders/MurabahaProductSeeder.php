<?php

namespace Database\Seeders;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use App\Models\Member;
use App\Models\ProductType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MurabahaProductSeeder extends Seeder
{
    use WithoutModelEvents;
    private static int $transCodeCounter = 1000000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset counter setiap kali seeder dijalankan
        self::$transCodeCounter = 1000000;

        // Ambil semua members
        $members = Member::all();

        if ($members->isEmpty()) {
            return; // Skip jika tidak ada member
        }

        $statuses = [
            FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            FinancingReqStatusEnum::PENDING_REVIEW->value,
            FinancingReqStatusEnum::PAID->value,
        ];

        $items = [
            ['name' => 'Motor Honda Vario 160', 'spec' => 'Motor Honda Vario 160cc Tahun 2024', 'price' => 50000000, 'type' => 'Kendaraan Roda Dua'],
            ['name' => 'Laptop ASUS VivoBook', 'spec' => 'Laptop ASUS VivoBook 15, Intel i5, RAM 8GB', 'price' => 30000000, 'type' => 'Elektronik'],
            ['name' => 'Mesin Jahit Singer', 'spec' => 'Mesin Jahit Singer Portable, Semi Otomatis', 'price' => 20000000, 'type' => 'Peralatan Usaha'],
            ['name' => 'Sepeda Motor Yamaha', 'spec' => 'Yamaha Vixion 150cc Tahun 2024', 'price' => 45000000, 'type' => 'Kendaraan Roda Dua'],
            ['name' => 'Smartphone iPhone 15', 'spec' => 'iPhone 15 Pro Max 256GB', 'price' => 25000000, 'type' => 'Elektronik'],
        ];

        // Generate 100 pembiayaan
        for ($j = 0; $j < 100; $j++) {
            $memberIndex = $j % $members->count();
            $member = $members[$memberIndex];
            $statusIndex = $j % count($statuses);
            $status = $statuses[$statusIndex];
            $itemIndex = $j % count($items);
            $item = $items[$itemIndex];

            if ($status === FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value) {
                $this->seedActiveFinancing($member, $item);
            } elseif ($status === FinancingReqStatusEnum::PENDING_REVIEW->value) {
                $this->seedPendingFinancing($member, $item);
            } else {
                $this->seedCompletedFinancing($member, $item);
            }
        }
    }

    private function getUniqueTransCode(): string
    {
        return 'TP' . str_pad(self::$transCodeCounter++, 8, '0', STR_PAD_LEFT);
    }

    private function seedActiveFinancing(Member $member, array $item): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? User::first();
        $margin = (int)($item['price'] * 0.1); // 10% margin
        $downPayment = (int)($item['price'] * 0.1); // 10% down payment

        $financing = Financing::create([
            'financing_transaction_code' => 'PM' . strtoupper(uniqid()),
            'member_id' => $member->id,
            'cost_price' => $item['price'],
            'margin_amount' => $margin,
            'down_payment' => $downPayment,
            'akad_date' => now()->subMonths(rand(1, 6)),
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin->id,
        ]);

        // Create Financing Item
        FinancingItem::create([
            'financing_id' => $financing->id,
            'name' => $item['name'],
            'specification' => $item['spec'],
            'qty' => 1,
            'condition' => 'Baru',
            'price_per_unit' => $item['price'],
            'product_type_id' => ProductType::where('product_type_name', $item['type'])->first()?->id,
        ]);

        // Create Installment (tenor 12 bulan)
        $tenor = 12;
        $installment = Installment::create([
            'financing_id' => $financing->id,
            'tenor' => $tenor,
            'due_day' => now()->day,
        ]);

        // Create Payment Transactions (sesuai bulan yang sudah lewat)
        $monthlyPayment = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $tenor;
        $akadDate = Carbon::parse($financing->akad_date);
        $paidMonths = now()->diffInMonths($akadDate);

        for ($i = 1; $i <= min($paidMonths, $tenor); $i++) {
            InstallmentPaymentTransaction::create([
                'installment_trans_code' => $this->getUniqueTransCode(),
                'installment_id' => $installment->id,
                'nominal' => $monthlyPayment,
                'payment_method' => PaymentMethodsEnum::CASHLESS->value,
                'is_early_repayment' => false,
                'payment_date' => $akadDate->copy()->addMonths($i),
                'updated_by' => $admin->id,
            ]);
        }
    }

    private function seedPendingFinancing(Member $member, array $item): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? User::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);

        $financing = Financing::create([
            'financing_transaction_code' => 'PM' . strtoupper(uniqid()),
            'member_id' => $member->id,
            'cost_price' => $item['price'],
            'margin_amount' => $margin,
            'down_payment' => $downPayment,
            'requested_date' => now(),
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin->id,
        ]);

        // Create Financing Item
        FinancingItem::create([
            'financing_id' => $financing->id,
            'name' => $item['name'],
            'specification' => $item['spec'],
            'qty' => 1,
            'condition' => 'Baru',
            'price_per_unit' => $item['price'],
            'product_type_id' => ProductType::where('product_type_name', $item['type'])->first()?->id,
        ]);
    }

    private function seedCompletedFinancing(Member $member, array $item): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? User::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);
        $tenor = 10;

        $financing = Financing::create([
            'financing_transaction_code' => 'PM' . strtoupper(uniqid()),
            'member_id' => $member->id,
            'cost_price' => $item['price'],
            'margin_amount' => $margin,
            'down_payment' => $downPayment,
            'akad_date' => now()->subMonths($tenor),
            'paid_date' => now(),
            'status' => FinancingReqStatusEnum::PAID->value,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin->id,
        ]);

        // Create Financing Item
        FinancingItem::create([
            'financing_id' => $financing->id,
            'name' => $item['name'],
            'specification' => $item['spec'],
            'qty' => 1,
            'condition' => 'Baru',
            'price_per_unit' => $item['price'],
            'product_type_id' => ProductType::where('product_type_name', $item['type'])->first()?->id,
        ]);

        // Create Installment
        $installment = Installment::create([
            'financing_id' => $financing->id,
            'tenor' => $tenor,
            'due_day' => now()->day,
        ]);

        // Create ALL Payment Transactions (semua sudah dibayar)
        $monthlyPayment = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $tenor;
        $akadDate = Carbon::parse($financing->akad_date);

        for ($i = 1; $i <= $tenor; $i++) {
            InstallmentPaymentTransaction::create([
                'installment_trans_code' => $this->getUniqueTransCode(),
                'installment_id' => $installment->id,
                'nominal' => $monthlyPayment,
                'payment_method' => PaymentMethodsEnum::CASH->value,
                'is_early_repayment' => false,
                'payment_date' => $akadDate->copy()->addMonths($i),
                'updated_by' => $admin->id,
            ]);
        }
    }
}

