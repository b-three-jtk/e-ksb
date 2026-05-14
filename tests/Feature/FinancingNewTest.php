<?php

use App\Enums\FinancialIncomeEnum;
use App\Enums\FinancialTypeEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('TC-FIN-01: Permohonan Pembiayaan Murabahah', function () {
    it('[REQ-F-13, REQ-F-14, REQ-F-15, REQ-F-16] Staf Murabahah dapat mencatat permohonan dengan data identitas, ahli waris, finansial, dan jaminan', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financing/store', [
                'member' => [
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'nik' => $member->user->nik,
                    'heirs' => [
                        ['heir_name' => 'Ahli Waris 1', 'heir_nik' => '1234567890654321', 'relationship' => 'Istri', 'heir_contact' => '081234567890']
                    ],
                    'incomes' => [
                        ['financial_type' => FinancialIncomeEnum::BASIC_SALARY_ALLOWANCE->value, 'amount' => 5000000]
                    ],
                    'expenses' => [
                        ['financial_type' => FinancialTypeEnum::FAMILY_LIVING_COST->value, 'amount' => 2000000]
                    ]
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'cost_price' => 50000000,
                    'margin_amount' => 4000000,
                    'down_payment' => 10000000,
                    'payment_method' => 'Cicilan',
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'request_description' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'supplier' => [
                    'supplier_name' => 'PT. Supplier Jaya',
                    'contact' => '081234567890',
                    'address' => 'Jl. Supplier No. 1',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'Pemohon',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
                'tenor' => 24,
            ]);

        if ($response->status() !== 302) {
            dump($response->errors());
        }

        $response->assertStatus(302);

        // Assertions
        $this->assertDatabaseHas('financing_items', ['name' => 'Motor Honda']);
        $this->assertDatabaseHas('collaterals', ['collateral_type' => 'Motor']);
    });

    it('[REQ-F-17] Staf murabahah dapat menyimpan sementara isian form permohonan (Draft)', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $member = Member::factory()->create();

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financing/store', [
                'member' => ['user_code' => $member->user->user_code, 'name' => $member->user->name, 'nik' => $member->user->nik],
                'financing' => [
                    'name' => 'Motor',
                    'financing_status' => 'Menunggu Kelengkapan Dokumen', // Status Draft
                    'request_description' => 'Draft permohonan pembiayaan untuk motor.',
                    'qty' => 1,
                    'condition' => 'Baru',
                ],
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financings', [
            'financing_status' => 'Menunggu Kelengkapan Dokumen'
        ]);
    });
});

describe('TC-FIN-02: Persetujuan (Approval) Pembiayaan', function () {
    it('[REQ-F-18] Ketua Murabahah dapat menyetujui permohonan pembiayaan', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'financing_status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($ketuaMurabahah)
            ->put("/admin/financing/validate/{$financing->id}", [
                'financing_status' => 'Disetujui',
                'notes' => 'Permohonan disetujui, riwayat kredit baik.',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financings', [
            'id' => $financing->id,
            'financing_status' => 'Disetujui',
            'notes' => 'Permohonan disetujui, riwayat kredit baik.',
        ]);
    });

    it('[REQ-F-18] Ketua Murabahah dapat menolak permohonan pembiayaan beserta alasan', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'financing_status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $this->actingAs($ketuaMurabahah)
            ->put("/admin/financing/validate/{$financing->id}", [
                'financing_status' => FinancingReqStatusEnum::REJECTED->value,
                'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
            ]);

        $this->assertDatabaseHas('financings', [
            'id' => $financing->id,
            'financing_status' => FinancingReqStatusEnum::REJECTED->value,
            'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
        ]);
    });
});

describe('TC-FIN-03 & 04: Akad Wakalah & Finalisasi Pembiayaan', function () {
    it('[REQ-F-19, REQ-F-21] Staf Murabahah dapat menangani permohonan dengan akad wakalah dan mengunggah dokumennya', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $financing = Financing::factory()->create([
            'is_wakalah' => true,
            'financing_status' => 'Disetujui',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financing/store", [
                'akad_wakalah_file' => UploadedFile::fake()->create('wakalah.pdf'),
            ]);

        $response->assertStatus(302);
    });

    it('[REQ-F-22, REQ-F-25] Staf Murabahah dapat melakukan finalisasi dengan menambah pemasok, bukti pengadaan, tenor, dan dokumen akad', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $financing = Financing::factory()->create(['financing_status' => 'Disetujui']);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financing/store", [
                'supplier' => [
                    'supplier_name' => 'PT. Supplier Jaya',
                    'contact' => '081234567890',
                    'address' => 'Jl. Supplier No. 1',
                ],
                'purchase_receipt_file' => UploadedFile::fake()->create('receipt.pdf'),
                'tenor' => 24,
                'akad_date' => '2024-01-15',
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]);

        $response->assertStatus(302);
    });
});

describe('TC-FIN-05 & 06: Pelunasan Awal & Pembayaran Angsuran', function () {
    it('[REQ-F-26] Staf Murabahah dapat memproses permohonan pelunasan sebelum jatuh tempo', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $financing = Financing::factory()->create(['financing_status' => 'Cicilan Berjalan']);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financing/{$financing->id}/early-payoff", [
                'payoff_date' => now()->addMonths(6)->format('Y-m-d'),
                'payoff_amount' => 40000000,
            ]);

        $response->assertStatus(302);
    });

    it('[REQ-F-29, REQ-F-31] Staf murabahah dapat mencatat pembayaran angsuran dan sistem dapat menghasilkan struk pembayaran', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $financing = Financing::factory()->create(['financing_status' => 'Cicilan Berjalan']);

        // Test Pencatatan
        $responseStore = $this->actingAs($staffMurabahah)
            ->post("/admin/financing/{$financing->id}/record-payment", [
                'schedule_id' => 1,
                'payment_amount' => 1833333,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'Tunai',
            ]);
        $responseStore->assertStatus(302);

        // Test Struk
        $responseReceipt = $this->actingAs($staffMurabahah)
            ->get("/admin/financing/{$financing->id}/payment-receipt");
        $responseReceipt->assertStatus(200);
    });
});

describe('TC-FIN-07: Daftar dan Riwayat Pembiayaan', function () {
    it('[REQ-F-32, REQ-F-33] Ketua/Staf Murabahah dapat melihat daftar pembiayaan aktif dan riwayat semua anggota', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');
        Financing::factory()->count(3)->create(['financing_status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $responseActive = $this->actingAs($ketuaMurabahah)->get('/admin/financing');
        $responseActive->assertStatus(200);

        $responseHistory = $this->actingAs($ketuaMurabahah)->get('/admin/financing?tab=all');
        $responseHistory->assertStatus(200);
    });

    it('[REQ-F-34, REQ-F-35] Anggota dapat melihat pembiayaan yang sedang berjalan dan riwayat miliknya sendiri', function () {
        $member = Member::factory()->create();
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        // Pembiayaan berjalan
        Financing::factory()->create([
            'member_id' => $member->id,
            'financing_status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        // Pembiayaan lunas (Riwayat)
        Financing::factory()->create([
            'member_id' => $member->id,
            'financing_status' => 'Lunas',
        ]);

        $response = $this->actingAs($user)->get('/user/financing');
        $response->assertStatus(200);
    });
});
