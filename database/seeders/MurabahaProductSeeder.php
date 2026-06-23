<?php

namespace Database\Seeders;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
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

        Member::factory()->count(100)->create();

        // Ambil semua members
        $members = Member::all();

        if ($members->isEmpty()) {
            return; // Skip jika tidak ada member
        }

        // Mapping skenario status dan kolektibilitas
        $scenarios = [
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'lancar'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'kurang_lancar'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'diragukan'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'macet'],
            ['status' => FinancingReqStatusEnum::PENDING_REVIEW->value, 'kolektibilitas' => null],
            ['status' => FinancingReqStatusEnum::PAID->value, 'kolektibilitas' => null],
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

            $scenarioIndex = $j % count($scenarios);
            $scenario = $scenarios[$scenarioIndex];

            $itemIndex = $j % count($items);
            $item = $items[$itemIndex];

            if ($scenario['status'] === FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value) {
                $this->seedActiveFinancing($member, $item, $scenario['kolektibilitas']);
            } elseif ($scenario['status'] === FinancingReqStatusEnum::PENDING_REVIEW->value) {
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

    private function seedActiveFinancing(Member $member, array $item, string $kolektibilitas = 'lancar'): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? User::first();
        $margin = (int)($item['price'] * 0.1); // 10% margin
        $downPayment = (int)($item['price'] * 0.1); // 10% down payment

        // 1. Atur Tenor & Rentang Waktu berdasarkan Kolektibilitas
        $tenor = 12;
        $akadMonthsAgo = 2; // Default lancar
        $unpaidMonthsAgo = 0; // Kapan tunggakan mulai terjadi

        switch ($kolektibilitas) {
            case 'kurang_lancar':
                $tenor = 24;
                $akadMonthsAgo = 10;
                $unpaidMonthsAgo = 5; // Nunggak 5 bulan
                break;
            case 'diragukan':
                $tenor = 24;
                $akadMonthsAgo = 15;
                $unpaidMonthsAgo = 8; // Nunggak 8 bulan
                break;
            case 'macet':
                $tenor = 12;
                $akadMonthsAgo = 18; // Kontrak habis 6 bulan lalu
                $unpaidMonthsAgo = 7; // Masih nunggak sejak 7 bulan lalu
                break;
            case 'lancar':
            default:
                $tenor = 12;
                $akadMonthsAgo = 2;
                break;
        }

        $akadDate = now()->subMonths($akadMonthsAgo)->startOfDay();

        $financing = Financing::create([
            'financing_transaction_code' => 'PM' . strtoupper(uniqid()),
            'member_id' => $member->id,
            'cost_price' => $item['price'],
            'margin_amount' => $margin,
            'down_payment' => $downPayment,
            'akad_date' => $akadDate,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'payment_method' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin?->id,
            'tenor' => $tenor,
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

        // 2. Buat Installment sesuai skenario kolektibilitas
        for ($i = 1; $i <= $tenor; $i++) {
            $monthlyPayment = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $tenor;
            $dueDate = $akadDate->copy()->addMonths($i);

            // Tentukan status pembayaran cicilan
            $isPaid = false;
            if ($kolektibilitas === 'lancar') {
                $isPaid = $dueDate->isPast();
            } else {
                $batasTunggakan = now()->subMonths($unpaidMonthsAgo)->startOfDay();
                $isPaid = $dueDate->isBefore($batasTunggakan);
            }

            $installment = Installment::create([
                'financing_id' => $financing->id,
                'installment_no' => $i,
                'due_date' => $dueDate,
                'amount' => $monthlyPayment,
                'status' => $isPaid ? InstallmentPaymentScheduleStatusEnum::PAID->value : InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);

            // Jika dibayar, buat history transaksinya
            if ($isPaid) {
                InstallmentPaymentTransaction::create([
                    'installment_trans_code' => $this->getUniqueTransCode(),
                    'installment_id' => $installment->id,
                    'nominal' => $monthlyPayment,
                    'payment_method' => PaymentMethodsEnum::CASHLESS->value,
                    'is_early_repayment' => false,
                    'payment_date' => $dueDate,
                    'updated_by' => $admin?->id,
                ]);

                $journal = Journal::create([
                    'tgl_transaksi' => $dueDate,
                    'created_by' => $admin?->id,
                ]);

                JournalEntry::create([
                    'journal_group_id' => $journal->id,
                    'no_ref_account' => '101',
                    'position' => 'Debit',
                    'nominal' => $monthlyPayment,
                    'updated_by' => $admin?->id,
                    'transaction_date' => $dueDate,
                ]);

                JournalEntry::create([
                    'journal_group_id' => $journal->id,
                    'no_ref_account' => '401',
                    'position' => 'Credit',
                    'nominal' => $monthlyPayment,
                    'updated_by' => $admin?->id,
                    'transaction_date' => $dueDate,
                ]);
            }
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
            'updated_by' => $admin?->id,
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

        $journal = Journal::create([
            'tgl_transaksi' => now(),
            'created_by' => $admin?->id,
        ]);

        JournalEntry::create([
            'journal_group_id' => $journal->id,
            'no_ref_account' => '103',
            'position' => 'Debit',
            'nominal' => $financing->cost_price,
            'updated_by' => $admin?->id,
            'transaction_date' => now(),
        ]);

        JournalEntry::create([
            'journal_group_id' => $journal->id,
            'no_ref_account' => '102',
            'position' => 'Credit',
            'nominal' => $financing->cost_price,
            'updated_by' => $admin?->id,
            'transaction_date' => now(),
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
            'updated_by' => $admin?->id,
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

        for ($i = 1; $i <= 12; $i++) {
            $monthlyPayment = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $tenor;
            $monthlyMargin = $financing->margin_amount / $tenor;
            $monthlyCostPrice = ($financing->cost_price - $financing->down_payment) / $tenor;

            $akadDate = Carbon::parse($financing->akad_date);
            $dueDate = $akadDate->copy()->addMonths($i);

            $installment = Installment::create([
                'financing_id' => $financing->id,
                'installment_no' => $i,
                'due_date' => $dueDate,
                'amount' => $monthlyPayment,
                'status' => $dueDate->isPast() ? InstallmentPaymentScheduleStatusEnum::PENDING->value : InstallmentPaymentScheduleStatusEnum::PAID->value,
            ]);

            InstallmentPaymentTransaction::create([
                'installment_trans_code' => $this->getUniqueTransCode(),
                'installment_id' => $installment->id,
                'nominal' => $monthlyPayment,
                'principal_amount' => $monthlyCostPrice,
                'margin_amount' => $monthlyMargin,
                'payment_method' => PaymentMethodsEnum::CASHLESS->value,
                'is_early_repayment' => false,
                'payment_date' => $dueDate,
                'updated_by' => $admin?->id,
            ]);

            $journal = Journal::create([
                'tgl_transaksi' => $dueDate,
                'created_by' => $admin?->id,
            ]);

            // kas
            JournalEntry::create([
                'journal_group_id' => $journal->id,
                'no_ref_account' => '101',
                'position' => 'Debit',
                'nominal' => $monthlyPayment,
                'updated_by' => $admin?->id,
                'transaction_date' => $dueDate,
            ]);

            // murabahah
            JournalEntry::create([
                'journal_group_id' => $journal->id,
                'no_ref_account' => '401',
                'position' => 'Credit',
                'nominal' => $monthlyMargin,
                'updated_by' => $admin?->id,
                'transaction_date' => $dueDate,
            ]);
        }
    }
}
