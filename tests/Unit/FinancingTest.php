<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Financing;
use App\Models\Member;
use App\Services\Admin\DasborService;
use App\Services\Admin\PembayaranAngsuranService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\ProductTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
    $this->seed(ProductTypeSeeder::class);
});

it('Menghitung detail pelunasan sebelum jatuh tempo', function () {
    $financing = new Financing();
    $financing->tenor = 10;
    $financing->cost_price = 12000;
    $financing->down_payment = 2000;
    $financing->margin_amount = 2000;

    $financing->setRelation('installment', collect([
        (object) ['status' => InstallmentPaymentScheduleStatusEnum::PAID->value],
        (object) ['status' => InstallmentPaymentScheduleStatusEnum::PAID->value],
        (object) ['status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value],
    ]));

    $service = new PembayaranAngsuranService();

    $result = $service->calculateDetails($financing);

    expect($result['total_paid_installments'])->toBe(2);

    expect($result['principal_per_month'])->toBe(1000);
    expect($result['margin_per_month'])->toBe(200);
    expect($result['installment_per_month'])->toBe(1200);

    expect($result['principal_paid'])->toBe(2000);
    expect($result['margin_paid'])->toBe(400);
    expect($result['total_paid_amount'])->toBe(2400);

    expect($result['qimah_haliyyah'])->toBe(10600);
    expect($result['repayment_total'])->toBe(8200);
});

it('Dapat memetakan seluruh kolektibilitas pembiayaan dengan akurat', function () {
    $targetDate = '2026-06-01';
    $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

    $statusActive = FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value;
    $statusScheduled = InstallmentPaymentScheduleStatusEnum::SCHEDULED->value;

    // 1. DATA LANCAR
    // Skenario: Belum waktunya bayar (Due date: Juli 2026)
    $lancar = Financing::create([
        'member_id' => $member->id,
        'status' => $statusActive,
        'akad_date' => '2026-01-01',
        'requested_date' => '2026-01-01',
        'tenor' => 12,
    ]);
    $lancar->installment()->create([
        'installment_no' => 1,
        'amount' => 1000,
        'due_date' => '2026-07-01',
        'status' => $statusScheduled,
    ]);

    // 2. DATA KURANG LANCAR
    // Skenario: Kontrak berjalan, nunggak 5 bulan (Due date: Januari 2026)
    // Syarat kode: tunggakan 4-6 bulan
    $kurangLancar = Financing::create([
        'member_id' => $member->id,
        'status' => $statusActive,
        'akad_date' => '2025-10-01',
        'requested_date' => '2025-10-01',
        'tenor' => 24, // Jatuh tempo akhir masih 2027
    ]);
    $kurangLancar->installment()->create([
        'installment_no' => 1,
        'amount' => 1000,
        'due_date' => '2026-01-01',
        'status' => $statusScheduled,
    ]);

    // 3. DATA DIRAGUKAN
    // Skenario: Kontrak berjalan, nunggak 8 bulan (Due date: Oktober 2025)
    // Syarat kode: tunggakan 7-12 bulan
    $diragukan = Financing::create([
        'member_id' => $member->id,
        'status' => $statusActive,
        'akad_date' => '2025-05-01',
        'requested_date' => '2025-05-01',
        'tenor' => 24, // Jatuh tempo akhir masih 2027
    ]);
    $diragukan->installment()->create([
        'installment_no' => 1,
        'amount' => 1000,
        'due_date' => '2025-10-01',
        'status' => $statusScheduled,
    ]);

    // 4. DATA MACET
    // Skenario: Kontrak sudah habis/tamat 5 bulan lalu, tapi masih ada tunggakan
    // Syarat kode: jatuh tempo pembiayaan terlewati > 2 bulan
    $macet = Financing::create([
        'member_id' => $member->id,
        'status' => $statusActive,
        'akad_date' => '2025-01-01',
        'requested_date' => '2025-01-01',
        'tenor' => 12, // Kontrak tamat pada Januari 2026
    ]);
    $macet->installment()->create([
        'installment_no' => 1,
        'amount' => 1000,
        'due_date' => '2025-12-01', // Angsuran bulan terakhir yang belum dibayar
        'status' => $statusScheduled,
    ]);

    $service = app(DasborService::class);
    $peta = $service->getPetaPembiayaan($targetDate);

    expect($peta)->toBeArray();
    expect($peta['Lancar'])->toBe(1);
    expect($peta['Kurang Lancar'])->toBe(1);
    expect($peta['Diragukan'])->toBe(1);
    expect($peta['Macet'])->toBe(1);

    expect(array_sum($peta))->toBe(4);
});
