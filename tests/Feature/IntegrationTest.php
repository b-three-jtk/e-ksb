<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Angsuran;
use App\Models\Anggota;
use App\Models\JenisBarang;
use App\Models\AkunSimpanan;
use App\Models\Pemasok;
use App\Models\Pengguna;
use Database\Seeders\AkunSeeder;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\JenisBarangSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
    $this->seed(PengaturanUmumSeeder::class);
    $this->seed(JenisBarangSeeder::class);
});

describe('IT01 Skenario Pembiayaan Murabahah', function () {
    beforeEach(function () {
        /** @var \Tests\TestCase $this */
        $this->userMember = Pengguna::factory()->create(['nama' => 'Leon S Kennedy', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->pjAnggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->pjAnggota->assignRole('Penanggung Jawab Anggota');

        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value, 'pj_anggota_id' => $this->pjAnggota->id]);

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

        $this->bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->bendahara->assignRole('Bendahara');

        $this->pemasok = Pemasok::create([
            'nama_pemasok' => 'PT. Pemasok Integrasi',
            'contact' => '081234567890',
            'alamat_pemasok' => 'Jl. Integrasi No. 1',
        ]);

        $danaAlokasi = \App\Models\Akun::where('nama_akun', 'Dana Alokasi Pembiayaan Murabahah')->first();
        if ($danaAlokasi) {
            $jurnal = \App\Models\Jurnal::create([
                'tgl_transaksi' => now()->format('Y-m-d'),
                'created_by' => $this->staffMurabahah->id,
            ]);
            \App\Models\DetailJurnal::create([
                'no_ref_akun' => $danaAlokasi->no_ref_akun,
                'posisi_akun' => 'Debit',
                'nominal' => 100000000,
                'updated_by' => $this->staffMurabahah->id,
                'jurnal_id' => $jurnal->id,
            ]);
        }

        $this->jenisBarang = JenisBarang::first();

        $this->payloadPengajuan = [
            'anggota'=> [
                'kode_pengguna' => $this->anggota->user->kode_pengguna,
                'nama' => 'Dhira Ramadini',
                'nik' => '1234567890123456',
                'no_telp' => '08123456789',
                'status_pekerjaan' => 'Karyawan Swasta',
                'ahli_waris' => [['nama_ahli_waris' => 'Ahli Waris', 'nik_ahli_waris' => '1234567890654321', 'hubungan' => 'Anak Laki-laki', 'kontak_ahli_waris' => '081234567890']],
            ],
            'jaminan' => [
                'jenis_jaminan' => 'Logam Mulia',
                'nama_pemilik' => 'Dhira Ramadini',
                'nilai_perkiraan_pasar' => 15000000,
                'lokasi_kondisi_jaminan' => 'Bandung',
            ],
            'income_slip_file' => UploadedFile::fake()->create('income.jpg'),
            'bank_book_file' => UploadedFile::fake()->create('bank.jpg'),
        ];
    });

    it('Skenario Lunas: Pengajuan -> Verifikasi -> Finalisasi', function () {
        /** @var \Tests\TestCase $this */
        // staf ngajuin pembiayaan cash
        $payload = $this->payloadPengajuan;
        $payload['pembiayaan'] = [
            'nama_barang' => 'Laptop ASUS',
            'jenis_barang_id' => $this->jenisBarang->id,
            'harga_perkiraan' => 10000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'tgl_akad' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'metode_pembayaran' => 'Tunai',
            'spesifikasi_barang' => 'Laptop untuk menunjang pekerjaan',
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/pembiayaan/store', $payload)->assertSessionHasNoErrors()->assertStatus(302);
        $pembiayaan = Pembiayaan::where('anggota_id', $this->anggota->id)->first();
        Log::info('Pembiayaan ID: '.$pembiayaan->id);

        // ketua nge-acc pembiayaan
        $this->actingAs($this->ketuaMurabahah)
            ->put("/admin/pembiayaan/validate/{$pembiayaan->id}", ['status' => 'Disetujui'])
            ->assertSessionHasNoErrors()
            ->assertStatus(302);

        // staf proses finalisasi, karena tunai status otomatis lunas
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/pembiayaan/finalize', array_merge($payload, [
                'pembiayaan' => [
                    'nama_barang' => 'Laptop ASUS',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'harga_beli_per_unit' => 10000000,
                    'harga_perolehan' => 10000000,
                    'margin_keuntungan' => 1000000, // Margin koperasi
                    'metode_pembayaran' => 'Tunai',
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::PAID->value,
                    'spesifikasi_barang' => 'Laptop untuk menunjang pekerjaan',
                    'harga_perkiraan' => 10000000,
                ],
                'pemasok' => [
                    'nama_pemasok' => 'PT. Pemasok Integrasi',
                    'alamat_pemasok' => 'Jl. Integrasi No. 1',
                    'contact' => '081234567890',
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]))->assertSessionHasNoErrors()->assertStatus(302);

        $this->assertDatabaseHas('pembiayaan', [
            'id' => $pembiayaan->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

    it('Skenario Tangguh: Pengajuan -> Verifikasi -> Finalisasi -> Bayar Angsuran (1 Kali)', function () {
        /** @var \Tests\TestCase $this */
        // staf ngajuin metode tangguh (bayar nanti sekalian)
        $payload = $this->payloadPengajuan;
        $payload['pembiayaan'] = [
            'nama_barang' => 'Bahan Baku Usaha',
            'jenis_barang_id' => $this->jenisBarang->id,
            'harga_perkiraan' => 5000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'tgl_akad' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'metode_pembayaran' => 'Tangguh',
            'spesifikasi_barang' => 'Tangguh bayar 1 bulan',
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/pembiayaan/store', $payload)->assertSessionHasNoErrors();
        $pembiayaan = Pembiayaan::where('anggota_id', $this->anggota->id)->first();

        // di-acc sama ketua
        $this->actingAs($this->ketuaMurabahah)->put("/admin/pembiayaan/validate/{$pembiayaan->id}", ['status' => 'Disetujui']);

        // lanjut difinalisasi sama staf
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/pembiayaan/finalize', array_merge($payload, [
                'pembiayaan' => [
                    'nama_barang' => 'Bahan Baku Usaha',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'harga_beli_per_unit' => 5000000,
                    'harga_perolehan' => 5000000,
                    'margin_keuntungan' => 500000,
                    'metode_pembayaran' => 'Tangguh',
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
            ]));

        // ceritanya pas finalisasi ini ngebikin 1 angsuran otomatis
        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 5500000,
            'tgl_jatuh_tempo' => now()->addMonth()->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        // tes bayar angsurannya sekali lunas
        $this->actingAs($this->pjAnggota)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/store", [
                'angsuran_id' => $angsuran->id,
                'pembiayaan_id' => $pembiayaan->id,
                'jumlah_angsuran_dibayar' => 5500000,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ])->assertSessionHasNoErrors()->assertStatus(302);

        $payment = \App\Models\PembayaranAngsuran::where('angsuran_id', $angsuran->id)->first();
        $this->actingAs($this->bendahara)
            ->post("/admin/pembiayaan/payments/{$payment->id}/verify")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pembayaran_angsuran', [
            'angsuran_id' => $angsuran->id,
            'jumlah_angsuran_dibayar' => 5500000,
            'status' => 'Diverifikasi',
        ]);

        // status pembiayaan harusnya lunas kalau angsuran udah beres semua
        $pembiayaan->update(['status' => FinancingReqStatusEnum::PAID->value]);
        $this->assertDatabaseHas('pembiayaan', ['id' => $pembiayaan->id, 'status' => FinancingReqStatusEnum::PAID->value]);
    });

    it('Skenario Cicilan & Pelunasan Sebelum Jatuh Tempo', function () {
        /** @var \Tests\TestCase $this */
        // ajuin pembiayaan pakai metode cicilan
        $payload = $this->payloadPengajuan;
        $payload['pembiayaan'] = [
            'nama_barang' => 'Motor Honda',
            'jenis_barang_id' => $this->jenisBarang->id,
            'harga_perkiraan' => 24000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'tgl_akad' => now()->format('Y-m-d'),
            'status' => 'Belum Ditinjau',
            'metode_pembayaran' => 'Cicilan',
            'spesifikasi_barang' => 'Cicilan 12 bulan',
            'tenor' => 12,
        ];

        $this->actingAs($this->staffMurabahah)->post('/admin/pembiayaan/store', $payload)->assertSessionHasNoErrors();
        $pembiayaan = Pembiayaan::where('anggota_id', $this->anggota->id)->first();

        // acc pengajuannya
        $this->actingAs($this->ketuaMurabahah)->put("/admin/pembiayaan/validate/{$pembiayaan->id}", ['status' => 'Disetujui']);

        // finalisasi dan generate angsuran
        $this->actingAs($this->staffMurabahah)
            ->post('/admin/pembiayaan/finalize', array_merge($payload, [
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => $this->jenisBarang->id,
                    'harga_beli_per_unit' => 24000000,
                    'harga_perolehan' => 24000000,
                    'margin_keuntungan' => 2400000,
                    'metode_pembayaran' => 'Cicilan',
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => now()->format('Y-m-d'),
                    'pemasok_id' => $this->pemasok->id,
                    'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    'spesifikasi_barang' => 'Cicilan 12 bulan',
                    'harga_perkiraan' => 24000000,
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
        $installment1 = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id, 'angsuran_ke' => 1, 'nominal_angsuran' => 2200000, 'status' => 'Terjadwal',
        ]);
        $installment2 = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id, 'angsuran_ke' => 2, 'nominal_angsuran' => 2200000, 'status' => 'Terjadwal',
        ]);

        // tes bayar cicilan bulan pertama
        $this->actingAs($this->pjAnggota)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/store", [
                'angsuran_id' => $installment1->id,
                'pembiayaan_id' => $pembiayaan->id,
                'jumlah_angsuran_dibayar' => 2200000,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ])->assertSessionHasNoErrors();

        $payment1 = \App\Models\PembayaranAngsuran::where('angsuran_id', $installment1->id)->first();
        $this->actingAs($this->bendahara)->post("/admin/pembiayaan/payments/{$payment1->id}/verify")->assertSessionHasNoErrors();

        // anggota mau lunasin sisa angsurannya lebih awal
        $this->actingAs($this->pjAnggota)
            ->post('/admin/pembiayaan/repayment', [
                'method' => 'Tunai',
                'angsuran_id' => $installment2->id,
            ])->assertSessionHasNoErrors()->assertStatus(302);
            
        $payment2 = \App\Models\PembayaranAngsuran::where('angsuran_id', $installment2->id)->first();
        if ($payment2) {
            $this->actingAs($this->bendahara)->post("/admin/pembiayaan/payments/{$payment2->id}/verify")->assertSessionHasNoErrors();
        }

        $this->assertDatabaseHas('pembiayaan', [
            'id' => $pembiayaan->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

});

describe('IT02 Skenario Pengunduran Diri Anggota', function () {
    beforeEach(function () {
        /** @var \Tests\TestCase $this */
        $this->userMember = Pengguna::factory()->create(['nama' => 'Claire Redfield', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value]);
    });

    it('Skenario Pengunduran Diri Anggota: Pengajuan -> Verifikasi', function () {
        /** @var \Tests\TestCase $this */
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

describe('IT03 Skenario Transaksi Simpanan', function () {
    beforeEach(function () {
        /** @var \Tests\TestCase $this */
        $this->userMember = Pengguna::factory()->create(['nama' => 'Ada Wong', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->staffSimpanan = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->staffSimpanan->assignRole('Penanggung Jawab Anggota');

        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value, 'pj_anggota_id' => $this->staffSimpanan->id]);

        $this->bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->bendahara->assignRole('Bendahara');
        
        $this->akunSimpanan = AkunSimpanan::factory()->create([
            'anggota_id' => $this->anggota->id,
            'saldo' => 0,
            'jenis_simpanan' => 'Tabungan Anggota',
        ]);
    });

    it('Skenario Penyetoran Tunai -> Verifikasi Bendahara', function () {
        /** @var \Tests\TestCase $this */
        $this->actingAs($this->staffSimpanan)
            ->post('/admin/savings/deposit', [
                'anggota_id' => $this->anggota->id,
                'saving_category' => 'Tabungan Anggota',
                'amount' => 500000,
                'date' => now()->format('Y-m-d'),
                'metode_pembayaran_simpanan' => 'Tunai',
                'catatan' => 'Setoran awal',
            ])->assertSessionHasNoErrors();

        $transaksi = \App\Models\TransaksiSimpanan::where('akun_simpanan_id', $this->akunSimpanan->id)->first();
        expect($transaksi)->not->toBeNull();

        $this->actingAs($this->bendahara)
            ->post("/admin/savings/{$transaksi->id}/verify")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transaksi_simpanan', [
            'id' => $transaksi->id,
            'status' => 'Diverifikasi',
        ]);
        
        $this->assertDatabaseHas('akun_simpanan', [
            'id' => $this->akunSimpanan->id,
            'saldo' => 500000,
        ]);
    });
});

describe('IT04 Skenario Pembayaran Angsuran Khusus', function () {
    beforeEach(function () {
        /** @var \Tests\TestCase $this */
        $this->userMember = Pengguna::factory()->create(['nama' => 'Albert Wesker', 'status' => UserStatusEnum::ACTIVE->value]);
        $this->userMember->assignRole('Anggota');
        $this->pjAnggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->pjAnggota->assignRole('Penanggung Jawab Anggota');

        $this->anggota = Anggota::factory()->create(['pengguna_id' => $this->userMember->id, 'status' => MemberStatusEnum::ACTIVE->value, 'pj_anggota_id' => $this->pjAnggota->id]);

        $this->bendahara = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $this->bendahara->assignRole('Bendahara');

        $this->jenisBarang = JenisBarang::first();

        // Buat data pembiayaan yang sudah aktif
        $this->pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $this->anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'metode_pembayaran' => 'Cicilan',
        ]);

        // Buat 1 angsuran terjadwal
        $this->angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $this->pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 500000,
            'tgl_jatuh_tempo' => now()->addMonth(),
            'status' => 'Terjadwal',
        ]);
    });

    it('Skenario Pembayaran Angsuran -> Verifikasi Bendahara', function () {
        /** @var \Tests\TestCase $this */
        // Penanggung Jawab Anggota menerima pembayaran dari anggota
        $this->actingAs($this->pjAnggota)
            ->post("/admin/pembiayaan/{$this->pembiayaan->id}/payments/store", [
                'angsuran_id' => $this->angsuran->id,
                'pembiayaan_id' => $this->pembiayaan->id,
                'jumlah_angsuran_dibayar' => 500000,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ])->assertSessionHasNoErrors();

        $payment = \App\Models\PembayaranAngsuran::where('angsuran_id', $this->angsuran->id)->first();
        expect($payment)->not->toBeNull();

        // Bendahara melakukan verifikasi penerimaan kas dari pembayaran angsuran
        $this->actingAs($this->bendahara)
            ->post("/admin/pembiayaan/payments/{$payment->id}/verify")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pembayaran_angsuran', [
            'id' => $payment->id,
            'status' => 'Diverifikasi',
        ]);
        
        $this->assertDatabaseHas('angsuran', [
            'id' => $this->angsuran->id,
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::PAID->value,
        ]);
    });
});
