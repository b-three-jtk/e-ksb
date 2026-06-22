<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\Installment;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\ProductTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
    $this->seed(ProductTypeSeeder::class);
});

describe('Aplikasi harus dapat menyediakan pencatatan permohonan pembiayaan murabahah anggota oleh staf murabahah.', function () {
    it('Staf Murabahah dapat mencatat permohonan dengan data yang valid', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financings/store', [
                'member' => [
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'nik' => $member->user->nik,
                    'phone_number' => $member->user->phone_number,
                    'employment_status' => 'Karyawan Swasta',
                    'heirs' => [
                        [
                            'heir_name' => 'Ahli Waris 1',
                            'heir_nik' => '1234567890654321',
                            'relationship' => 'Istri',
                            'heir_contact' => '081234567890'
                        ]
                    ],
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'product_type_id' => 1,
                    'predicted_cost_price' => 50000000,
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'specification' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'Pemohon',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financings', [
            'predicted_cost_price' => 50000000,
        ]);
        $this->assertDatabaseHas('financing_items', ['name' => 'Motor Honda']);
        $this->assertDatabaseHas('collaterals', ['collateral_type' => 'Motor']);
    });

    it('Pemohon yang tidak berstatus aktif tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');

        $user = User::factory()->create(['status' => UserStatusEnum::INACTIVE->value]);
        $member = Member::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financings/store', [
                'member' => [
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'nik' => $member->user->nik,
                    'phone_number' => $member->user->phone_number,
                    'employment_status' => 'Karyawan Swasta',
                    'heirs' => [
                        [
                            'heir_name' => 'Ahli Waris 1',
                            'heir_nik' => '1234567890654321',
                            'relationship' => 'Istri',
                            'heir_contact' => '081234567890'
                        ]
                    ],
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'product_type_id' => 1,
                    'predicted_cost_price' => 50000000,
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'specification' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'Pemohon',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => "Gagal menyimpan permohonan: Pemohon harus dalam status aktif"
        ]);
    });

    it('Pemohon yang masih mempunyai tunggakan tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');

        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $member = Member::factory()->create(['user_id' => $user->id]);

        Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financings/store', [
                'member' => [
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'nik' => $member->user->nik,
                    'phone_number' => $member->user->phone_number,
                    'employment_status' => 'Karyawan Swasta',
                    'heirs' => [
                        [
                            'heir_name' => 'Ahli Waris 1',
                            'heir_nik' => '1234567890654321',
                            'relationship' => 'Istri',
                            'heir_contact' => '081234567890'
                        ]
                    ],
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'product_type_id' => 1,
                    'predicted_cost_price' => 50000000,
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'specification' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'Pemohon',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => "Gagal menyimpan permohonan: Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses"
        ]);
    });

    it('Pemohon yang tidak mempunyai tabungan anggota yang sudah berjalan minimal 1 bulan tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financings/store', [
                'member' => [
                    'user_code' => $member->user->user_code,
                    'name' => $member->user->name,
                    'nik' => $member->user->nik,
                    'phone_number' => $member->user->phone_number,
                    'employment_status' => 'Karyawan Swasta',
                    'heirs' => [
                        [
                            'heir_name' => 'Ahli Waris 1',
                            'heir_nik' => '1234567890654321',
                            'relationship' => 'Istri',
                            'heir_contact' => '081234567890'
                        ]
                    ],
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'product_type_id' => 1,
                    'predicted_cost_price' => 50000000,
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'specification' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'Pemohon',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => 'Gagal menyimpan permohonan: Pemohon harus memiliki simpanan aktif minimal satu bulan'
        ]);
    });

    it('Selain Staf Murabahah tidak dapat mencatat permohonan pembiayaan', function () {
        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Ketua Murabahah');

        $response = $this->actingAs($user)
            ->post('/admin/financings/store', [
                'member' => ['user_code' => 'M001', 'name' => 'John Doe', 'nik' => '1234567890123456'],
                'financing' => [
                    'name' => 'Motor',
                    'status' => 'Belum Ditinjau',
                    'specification' => 'Permohonan pembiayaan untuk motor.',
                    'qty' => 1,
                    'condition' => 'Baru',
                ],
                'collateral' => [
                    'collateral_type' => 'Motor',
                    'owner_name' => 'John Doe',
                    'estimated_market_value' => 30000000,
                    'collateral_location' => 'Bandung',
                ],
            ]);

        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pencatatan permohonan pembiayaan murabahah dengan akad wakalah oleh anggota sebagai perwakilan (muwakkil) dari koperasi.', function () {
    it('Staf Murabahah dapat melakukan finalisasi pembiayaan murabahah bil wakalah', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');
        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');

        SavingAccount::factory()->create([
            'member_id' => $member->id,
            'balance' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'PT. Supplier Jaya',
            'contact' => '081234567890',
            'address' => 'Jl. Supplier No. 1',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/financings/finalize', [
                'member' => [
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'phone_number' => $user->phone_number,
                    'employment_status' => 'Karyawan Swasta',
                    'heirs' => [
                        [
                            'heir_name' => 'Ada Wong',
                            'heir_nik' => '1234567890654321',
                            'relationship' => 'Istri',
                            'heir_contact' => '081234567890'
                        ]
                    ],
                ],
                'financing' => [
                    'name' => 'Motor Honda',
                    'product_type_id' => 1,
                    'predicted_cost_price' => 50000000,
                    'price_per_unit' => 50000000,
                    'cost_price' => 50000000,
                    'margin_amount' => 10000000,
                    'payment_method' => 'Cicilan',
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => '2024-01-01',
                    'akad_wakalah_date' => '2024-01-02',
                    'status' => 'Angsuran Berjalan',
                    'supplier_id' => $supplier->id,
                    'specification' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
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
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
                'akad_wakalah_file' => UploadedFile::fake()->create('akad_wakalah.pdf'),
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financings', [
            'cost_price' => 50000000,
            'margin_amount' => 10000000,
            'status' => 'Angsuran Berjalan',
        ]);
        $this->assertDatabaseHas('financing_items', ['name' => 'Motor Honda']);
        $this->assertDatabaseHas('collaterals', ['collateral_type' => 'Motor']);
    });
});

describe('Aplikasi harus menyediakan verifikasi permohonan pembiayaan murabahah beserta catatan pemeriksaan oleh ketua murabahah atau ketua koperasi.', function () {
    it('Ketua Murabahah dapat menyetujui permohonan pembiayaan', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($ketuaMurabahah)
            ->put("/admin/financings/validate/{$financing->id}", [
                'status' => 'Disetujui',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financing_verifications', [
            'financing_id' => $financing->id,
            'final_verification_status' => 'Disetujui',
        ]);
    });

    it('Ketua Murabahah dapat menolak permohonan pembiayaan beserta alasan', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $this->actingAs($ketuaMurabahah)
            ->put("/admin/financings/validate/{$financing->id}", [
                'status' => FinancingReqStatusEnum::REJECTED->value,
                'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
            ]);

        $this->assertDatabaseHas('financing_verifications', [
            'financing_id' => $financing->id,
            'final_verification_status' => FinancingReqStatusEnum::REJECTED->value,
            'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
        ]);
    });

    it('Ketua koperasi dapat menyetujui permohonan pembiayaan yang diajukan oleh ketua murabahah', function () {
        $ketuaKoperasi = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaKoperasi->assignRole('Ketua');

        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahahMember = Member::factory()->create(['user_id' => $ketuaMurabahah->id, 'status' => MemberStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'member_id' => $ketuaMurabahahMember->id,
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($ketuaKoperasi)
            ->put("/admin/financings/validate/{$financing->id}", [
                'status' => 'Disetujui',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financing_verifications', [
            'financing_id' => $financing->id,
            'final_verification_status' => 'Disetujui',
        ]);
    });
});

describe('Aplikasi harus menyediakan daftar pembiayaan murabahah untuk ketua koperasi, ketua murabahah, dan staf murabahah.', function () {
    it('Ketua Murabahah dapat melihat daftar pembiayaan aktif dan riwayat semua anggota', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');
        Financing::factory()->count(3)->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $responseActive = $this->actingAs($ketuaMurabahah)->get('/admin/financings');
        $responseActive->assertStatus(200);
    });

    it('Selain pengurus terkait tidak dapat mengakses daftar pembiayaan', function () {
        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Sekretaris');

        $response = $this->actingAs($user)->get('/admin/financings');
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan detail pembiayaan murabahah yang memuat riwayat pembayaran.', function () {
    it('Ketua Murabahah dapat melihat detail pembiayaan beserta riwayat pembayaran', function () {
        $ketuaMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->assignRole('Ketua Murabahah');
        $financing = Financing::factory()->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $response = $this->actingAs($ketuaMurabahah)->get("/admin/financings/show/{$financing->id}");
        $response->assertStatus(200);
    });

    it('Selain pengurus terkait tidak dapat mengakses detail pembiayaan', function () {
        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Sekretaris');
        $financing = Financing::factory()->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $response = $this->actingAs($user)->get("/admin/financings/show/{$financing->id}");
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan informasi pembiayaan murabahah yang masih berjalan dan selesai bagi anggota.', function () {
    it('Anggota dapat melihat pembiayaan yang masih berjalan dan selesai', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);

        $response = $this->actingAs($user)->get('/user/financings');
        $response2 = $this->actingAs($user)->get("/user/financings/show/{$financing->id}");
        $response->assertStatus(200);
        $response2->assertStatus(200);
    });

    it('Anggota tidak dapat melihat pembiayaan anggota lain', function () {
        $member1 = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user1 = User::where('id', $member1->user_id)->first();
        $user1->assignRole('Anggota');

        $member2 = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $financing2 = Financing::factory()->create([
            'member_id' => $member2->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $response = $this->actingAs($user1)->get("/user/financings/show/{$financing2->id}");
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus dapat mengirimkan notifikasi jatuh tempo pembayaran angsuran kepada masing-masing anggota maksimal H-1 jatuh tempo pembayaran melalui aplikasi.', function () {
    it('Sistem mengirimkan notifikasi H-1 sebelum jatuh tempo pembayaran angsuran', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $this->artisan('notifications:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'member_id' => $member->id,
        ]);
    });
});

describe('Aplikasi harus menyediakan pemantauan notifikasi koperasi oleh penanggung jawab anggota', function () {
    it('Penanggung Jawab Anggota dapat melihat notifikasi terkait pembiayaan anggota', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $pjAnggota = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjAnggota->assignRole('Penanggung Jawab Anggota');

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $this->artisan('notifications:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'member_id' => $member->id,
        ]);

        $response = $this->actingAs($pjAnggota)->get('/admin/notifications');
        $response->assertStatus(200);
    });
});

describe('Aplikasi harus dapat menyediakan pencatatan transaksi pembayaran angsuran piutang murabahah oleh staf murabahah.', function () {
    it('Staf Murabahah dapat mencatat pembayaran angsuran', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');
        $financing = Financing::factory()->create(['status' => 'Angsuran Berjalan']);

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 1833333,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financings/{$financing}/payments/store", [
                'installment_id' => $installment->id,
                'financing_id' => $financing->id,
                'nominal' => 1833333,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'Tunai',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('installment_payment_transactions', [
            'installment_id' => $installment->id,
            'nominal' => 1833333,
        ]);
    });

    it('Selain Staf Murabahah tidak dapat mencatat pembayaran angsuran', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Ketua Murabahah');
        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 1833333,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);
        $response = $this->actingAs($user)
            ->post("/admin/financings/{$financing->id}/payments/store", [
                'installment_id' => $installment->id,
                'financing_id' => $financing->id,
                'nominal' => 1833333,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'Tunai',
            ]);

        $response->assertStatus(403);
    });
});

describe('Aplikasi harus dapat menyediakan penjadwalan ulang pembayaran angsuran pembiayaan oleh staf murabahah', function () {
    it('Staf Murabahah dapat menjadwalkan ulang pembayaran angsuran', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 1833333,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financings/{$financing->id}/payments/reschedule", [
                'installment_id' => $installment->id,
                'due_date' => now()->addDays(7)->format('Y-m-d'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);
    });
});

describe('Aplikasi harus menyediakan pencatatan permohonan pelunasan sebelum jatuh tempo dari anggota oleh staf murabahah.', function () {
    it('Staf Murabahah dapat memproses permohonan pelunasan sebelum jatuh tempo', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $staffMurabahah = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->assignRole('Staf Murabahah');

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        FinancingItem::factory()->create([
            'financing_id' => $financing->id,
            'name' => 'Motor Honda',
            'price_per_unit' => 50000000,
            'qty' => 1,
            'condition' => 'Baru',
        ]);

        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 1833333,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/financings/repayment", [
                'method' => 'Tunai',
                'installment_id' => $installment->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('financings', [
            'id' => $financing->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

    it('Selain Staf Murabahah tidak dapat memproses permohonan pelunasan sebelum jatuh tempo', function () {
        $member = Member::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = User::where('id', $member->user_id)->first();
        $user->assignRole('Anggota');

        $user = User::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Ketua Murabahah');

        $financing = Financing::factory()->create([
            'member_id' => $member->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'akad_date' => now()->subMonths(11),
        ]);

        FinancingItem::factory()->create([
            'financing_id' => $financing->id,
            'name' => 'Motor Honda',
            'price_per_unit' => 50000000,
            'qty' => 1,
            'condition' => 'Baru',
        ]);

        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 1833333,
            'due_date' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user)
            ->post("/admin/financings/repayment", [
                'method' => 'Tunai',
                'installment_id' => $installment->id,
            ]);

        $response->assertStatus(403);
    });
});
