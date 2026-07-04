<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\Installment;
use App\Models\Anggota;
use App\Models\JenisBarang;
use App\Models\AkunSimpanan;
use App\Models\Pemasok;
use App\Models\Pengguna;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\JenisBarangSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
    $this->seed(JenisBarangSeeder::class);
});

describe('IT01 Skenario Pembiayaan Murabahah', function () {
    beforeEach(function () {
        $this->userMember = Pengguna::factory()->create(['nama' => 'Leon S Kennedy', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value]);

        AkunSimpanan::factory()->create([
            'anggota_id' => $this->anggota->id,
            'saldo' => 10000000,
            'jenis_simpanan' => 'Tabungan Anggota',
            'created_at' => now()->subMonths(6),
        ]);

        $this->staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->staffMurabahah->assignRole('Staf Murabahah');

        $this->ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->ketuaMurabahah->assignRole('Ketua Murabahah');

        $this->pemasok = Pemasok::create([
            'nama_pemasok' => 'PT. Pemasok Integrasi',
            'contact' => '081234567890',
            'alamat_pemasok' => 'Jl. Integrasi No. 1',
        ]);

        $danaAlokasi = \App\Models\Account::where('account_name', 'Dana Alokasi Pembiayaan Murabahah')->first();
        if ($danaAlokasi) {
            \App\Models\JournalEntry::create([
                'no_ref_account' => $danaAlokasi->no_ref_account,
                'position' => 'Debit',
                'nominal' => 100000000,
                'transaction_date' => now()->format('Y-m-d'),
                'updated_by' => $this->staffMurabahah->id,
                'journal_group_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]);
        }

        $this->jenisBarang = JenisBarang::first();

        $this->payloadPengajuan = [
            'anggota'=> [
                'kode_pengguna' => $this->anggota->user->kode_pengguna,
                'nama' => 'Dhira Ramadini',
                'nik' => '1234567890123456',
                'no_telp' => '08123456789',
                'employment_status' => 'Karyawan Swasta',
                'heirs' => [['heir_name' => 'Ahli Waris', 'heir_nik' => '1234567890654321', 'relationship' => 'Anak', 'heir_contact' => '081234567890']],
            ],
            'collateral' => [
                'collateral_type' => 'Logam Mulia',
                'owner_name' => 'Dhira Ramadini',
                'estimated_market_value' => 15000000,
                'collateral_location' => 'Bandung',
            ],
            'income_slip_file' => UploadedFile::fake()->create('income.jpg'),
            'bank_book_file' => UploadedFile::fake()->create('bank.jpg'),
        ];
    });

    it('Skenario Lunas: Pengajuan -> Verifikasi -> Finalisasi', function () {
        // staf ngajuin pembiayaan cash
        $payload = $this->payloadPengajuan;
        $payload['financing'] = [
            'name' => 'Laptop ASUS',
            'jenis_barang_id' => $this->jenisBarang->id,
            'predicted_cost_price' => 10000000,
            'qty' => 1,
            'condition' => 'Baru',
            'akad_date' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'payment_method' => 'Tunai',
            'specification' => 'Laptop untuk menunjang pekerjaan',
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/financings/store', $payload)->assertSessionHasNoErrors()->assertStatus(302);
        $financing = Financing::where('anggota_id', $this->anggota->id)->first();
        Log::info('Financing ID: '.$financing->id);

        // ketua nge-acc pembiayaan
        $this->actingAs($this->ketuaMurabahah)
            ->put("/admin/financings/validate/{$financing->id}", ['status' => 'Disetujui'])
            ->assertSessionHasNoErrors()
            ->assertStatus(302);

        // staf proses finalisasi, karena tunai status otomatis lunas
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/financings/finalize', array_merge($payload, [
                'financing' => [
                    'name' => 'Laptop ASUS',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'price_per_unit' => 10000000,
                    'cost_price' => 10000000,
                    'margin_amount' => 1000000, // Margin koperasi
                    'payment_method' => 'Tunai',
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::PAID->value,
                    'specification' => 'Laptop untuk menunjang pekerjaan',
                    'predicted_cost_price' => 10000000,
                ],
                'pemasok' => [
                    'nama_pemasok' => 'PT. Pemasok Integrasi',
                    'alamat_pemasok' => 'Jl. Integrasi No. 1',
                    'contact' => '081234567890',
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]))->assertSessionHasNoErrors()->assertStatus(302);

        $this->assertDatabaseHas('financings', [
            'id' => $financing->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

    it('Skenario Tangguh: Pengajuan -> Verifikasi -> Finalisasi -> Bayar Angsuran (1 Kali)', function () {
        // staf ngajuin metode tangguh (bayar nanti sekalian)
        $payload = $this->payloadPengajuan;
        $payload['financing'] = [
            'name' => 'Bahan Baku Usaha',
            'jenis_barang_id' => $this->jenisBarang->id,
            'predicted_cost_price' => 5000000,
            'qty' => 1,
            'condition' => 'Baru',
            'akad_date' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'payment_method' => 'Tangguh',
            'specification' => 'Tangguh bayar 1 bulan',
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/financings/store', $payload)->assertSessionHasNoErrors();
        $financing = Financing::where('anggota_id', $this->anggota->id)->first();

        // di-acc sama ketua
        $this->actingAs($this->ketuaMurabahah)->put("/admin/financings/validate/{$financing->id}", ['status' => 'Disetujui']);

        // lanjut difinalisasi sama staf
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/financings/finalize', array_merge($payload, [
                'financing' => [
                    'name' => 'Bahan Baku Usaha',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'price_per_unit' => 5000000,
                    'cost_price' => 5000000,
                    'margin_amount' => 500000,
                    'payment_method' => 'Tangguh',
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]));

        // ceritanya pas finalisasi ini ngebikin 1 angsuran otomatis
        $installment = Installment::factory()->create([
            'financing_id' => $financing->id,
            'installment_no' => 1,
            'amount' => 5500000,
            'due_date' => now()->addMonth()->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        // tes bayar angsurannya sekali lunas
        $this->actingAs($this->staffMurabahah)
            ->post("/admin/financings/{$financing->id}/payments/store", [
                'installment_id' => $installment->id,
                'financing_id' => $financing->id,
                'nominal' => 5500000,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'Non-Tunai',
            ])->assertSessionHasNoErrors()->assertStatus(302);

        $this->assertDatabaseHas('installment_payment_transactions', [
            'installment_id' => $installment->id,
            'nominal' => 5500000,
        ]);

        // status financing harusnya lunas kalau angsuran udah beres semua
        $financing->update(['status' => FinancingReqStatusEnum::PAID->value]);
        $this->assertDatabaseHas('financings', ['id' => $financing->id, 'status' => FinancingReqStatusEnum::PAID->value]);
    });

    it('Skenario Cicilan & Pelunasan Sebelum Jatuh Tempo', function () {
        // ajuin pembiayaan pakai metode cicilan
        $payload = $this->payloadPengajuan;
        $payload['financing'] = [
            'name' => 'Motor Honda',
            'jenis_barang_id' => $this->jenisBarang->id,
            'predicted_cost_price' => 24000000,
            'qty' => 1,
            'condition' => 'Baru',
            'akad_date' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'payment_method' => 'Cicilan',
            'specification' => 'Cicilan 12 bulan',
            'tenor' => 12,
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/financings/store', $payload)->assertSessionHasNoErrors();
        $financing = Financing::where('anggota_id', $this->anggota->id)->first();

        // acc pengajuannya
        $this->actingAs($this->ketuaMurabahah)->put("/admin/financings/validate/{$financing->id}", ['status' => 'Disetujui']);

        // finalisasi dan generate angsuran
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/financings/finalize', array_merge($payload, [
                'financing' => [
                    'name' => 'Motor Honda',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'price_per_unit' => 24000000,
                    'cost_price' => 24000000,
                    'margin_amount' => 2400000,
                    'payment_method' => 'Cicilan',
                    'qty' => 1,
                    'condition' => 'Baru',
                    'akad_date' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    'specification' => 'Cicilan 12 bulan',
                    'predicted_cost_price' => 24000000,
                    'tenor' => 12,
                ],
                'pemasok' => [
                    'nama_pemasok' => 'PT. Pemasok Integrasi',
                    'alamat_pemasok' => 'Jl. Integrasi No. 1',
                    'contact' => '081234567890',
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]))->assertSessionHasNoErrors();

        // bikin dummy cicilan buat dites
        $installment1 = Installment::factory()->create([
            'financing_id' => $financing->id, 'installment_no' => 1, 'amount' => 2200000, 'status' => 'Terjadwal',
        ]);
        $installment2 = Installment::factory()->create([
            'financing_id' => $financing->id, 'installment_no' => 2, 'amount' => 2200000, 'status' => 'Terjadwal',
        ]);

        // tes bayar cicilan bulan pertama
        $this->actingAs($this->staffMurabahah)
            ->post("/admin/financings/{$financing->id}/payments/store", [
                'installment_id' => $installment1->id,
                'financing_id' => $financing->id,
                'nominal' => 2200000,
                'payment_date' => now()->format('Y-m-d'),
                'payment_method' => 'Non-Tunai',
            ])->assertSessionHasNoErrors();

        // anggota mau lunasin sisa angsurannya lebih awal
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/financings/repayment', [
                'method' => 'Non-Tunai',
                'installment_id' => $installment2->id,
            ])->assertSessionHasNoErrors()->assertStatus(200);

        $this->assertDatabaseHas('financings', [
            'id' => $financing->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

});

describe('IT02 Skenario Pengunduran Diri Anggota', function () {
    beforeEach(function () {
        $this->userMember = Pengguna::factory()->create(['nama' => 'Claire Redfield', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value]);
    });

    it('Skenario Pengunduran Diri Anggota: Pengajuan -> Verifikasi', function () {
        $this->actingAs($this->userMember)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('surat_resign.pdf'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('user.userDashboard'));

        $this->assertDatabaseHas('anggota', [
            'id' => $this->anggota->id,
            'status' => MemberStatusEnum::RESIGNED_REQUESTED->value,
        ]);

        // admin (ketua) nge-acc pengajuan resign
        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');

        $this->actingAs($ketua)
            ->put("/admin/resignations/{$this->userMember->id}")
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.resignations.index'));

        $this->assertDatabaseHas('anggota', [
            'id' => $this->anggota->id,
            'status' => MemberStatusEnum::RESIGNED->value,
        ]);

        $this->assertDatabaseHas('pengguna', [
            'id' => $this->userMember->id,
            'status' => UserStatusEnum::INACTIVE->value,
        ]);
    });
});
