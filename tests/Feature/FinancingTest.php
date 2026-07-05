<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\Pembiayaan;
use App\Models\ObjekPembiayaan;
use App\Models\GlobalSetting;
use App\Models\Angsuran;
use App\Models\PembayaranAngsuran;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use App\Models\Pemasok;
use App\Models\Pengguna;
use Database\Seeders\AccountSeeder;
use Database\Seeders\GlobalSettingSeeder;
use Database\Seeders\JenisBarangSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AccountSeeder::class);
    $this->seed(GlobalSettingSeeder::class);
    $this->seed(JenisBarangSeeder::class);
});

describe('Aplikasi harus dapat menyediakan pencatatan permohonan pembiayaan murabahah anggota oleh staf murabahah.', function () {
    it('Staf Murabahah dapat mencatat permohonan dengan data yang valid', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota'=> [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [
                        [
                            'nama_ahli_waris' => 'Ahli Waris 1',
                            'nik_ahli_waris' => '1234567890654321',
                            'hubungan' => 'Istri',
                            'kontak_ahli_waris' => '081234567890'
                        ]
                    ],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => \App\Models\JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pembiayaan', [
            'harga_perkiraan' => 50000000,
        ]);
        $this->assertDatabaseHas('objek_pembiayaan', ['nama_barang' => 'Motor Honda']);
        $this->assertDatabaseHas('jaminan', ['jenis_jaminan' => 'Motor']);
    });

    it('Pemohon yang tidak berstatus aktif tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::INACTIVE->value]);
        $anggota = Anggota::factory()->create(['pengguna_id' => $user->id]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota'=> [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [
                        [
                            'nama_ahli_waris' => 'Ahli Waris 1',
                            'nik_ahli_waris' => '1234567890654321',
                            'hubungan' => 'Istri',
                            'kontak_ahli_waris' => '081234567890'
                        ]
                    ],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => \App\Models\JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => "Gagal menyimpan permohonan: Pemohon harus dalam status aktif"
        ]);
    });

    it('Pemohon yang masih mempunyai tunggakan tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $anggota = Anggota::factory()->create(['pengguna_id' => $user->id]);

        Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota'=> [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [
                        [
                            'nama_ahli_waris' => 'Ahli Waris 1',
                            'nik_ahli_waris' => '1234567890654321',
                            'hubungan' => 'Istri',
                            'kontak_ahli_waris' => '081234567890'
                        ]
                    ],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => \App\Models\JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => "Gagal menyimpan permohonan: Pemohon masih memiliki pembiayaan yang sedang berjalan atau dalam proses"
        ]);
    });

    it('Pemohon yang tidak mempunyai tabungan anggota yang sudah berjalan minimal 1 bulan tidak dapat mengajukan pembiayaan', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota'=> [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [
                        [
                            'nama_ahli_waris' => 'Ahli Waris 1',
                            'nik_ahli_waris' => '1234567890654321',
                            'hubungan' => 'Istri',
                            'kontak_ahli_waris' => '081234567890'
                        ]
                    ],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => \App\Models\JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => '2024-01-01',
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasErrors([
            'error' => 'Gagal menyimpan permohonan: Pemohon harus memiliki simpanan aktif minimal satu bulan'
        ]);
    });

    it('Selain Staf Murabahah tidak dapat mencatat permohonan pembiayaan', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Ketua Murabahah');

        $response = $this->actingAs($user)
            ->post('/admin/pembiayaan/store', [
                'anggota'=> ['kode_pengguna' => 'M001', 'nama' => 'John Doe', 'nik' => '1234567890123456'],
                'pembiayaan' => [
                    'nama_barang' => 'Motor',
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Permohonan pembiayaan untuk motor.',
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'John Doe',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
            ]);

        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan pencatatan permohonan pembiayaan murabahah dengan akad wakalah oleh anggota sebagai perwakilan (muwakkil) dari koperasi.', function () {
    it('Staf Murabahah dapat melakukan finalisasi pembiayaan murabahah bil wakalah', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        $pemasok = Pemasok::create([
            'nama_pemasok' => 'PT. Pemasok Jaya',
            'contact' => '081234567890',
            'alamat_pemasok' => 'Jl. Pemasok No. 1',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/finalize', [
                'anggota'=> [
                    'kode_pengguna' => $user->kode_pengguna,
                    'nama' => $user->nama,
                    'nik' => $user->nik,
                    'no_telp' => $user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [
                        [
                            'nama_ahli_waris' => 'Ada Wong',
                            'nik_ahli_waris' => '1234567890654321',
                            'hubungan' => 'Istri',
                            'kontak_ahli_waris' => '081234567890'
                        ]
                    ],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => \App\Models\JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'harga_beli_per_unit' => 50000000,
                    'harga_perolehan' => 50000000,
                    'margin_keuntungan' => 10000000,
                    'metode_pembayaran' => 'Cicilan',
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => '2024-01-01',
                    'akad_wakalah_date' => '2024-01-02',
                    'status' => 'Angsuran Berjalan',
                    'pemasok_id' => $pemasok->id,
                    'spesifikasi_barang' => 'Pembiayaan untuk pembelian motor Honda terbaru.',
                ],
                'pemasok' => [
                    'nama_pemasok' => 'PT. Pemasok Jaya',
                    'contact' => '081234567890',
                    'alamat_pemasok' => 'Jl. Pemasok No. 1',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
                'akad_wakalah_file' => UploadedFile::fake()->create('akad_wakalah.pdf'),
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('pembiayaan', [
            'harga_perolehan' => 50000000,
            'margin_keuntungan' => 10000000,
            'status' => 'Angsuran Berjalan',
        ]);
        $this->assertDatabaseHas('objek_pembiayaan', ['nama_barang' => 'Motor Honda']);
        $this->assertDatabaseHas('jaminan', ['jenis_jaminan' => 'Motor']);
    });
});

describe('Aplikasi harus menyediakan verifikasi permohonan pembiayaan murabahah beserta catatan pemeriksaan oleh ketua murabahah atau ketua koperasi.', function () {
    it('Ketua Murabahah dapat menyetujui permohonan pembiayaan', function () {
        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($ketuaMurabahah)
            ->put("/admin/pembiayaan/validate/{$pembiayaan->id}", [
                'status' => 'Disetujui',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financing_verifications', [
            'pembiayaan_id' => $pembiayaan->id,
            'final_verification_status' => 'Disetujui',
        ]);
    });

    it('Ketua Murabahah dapat menolak permohonan pembiayaan beserta alasan', function () {
        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $this->actingAs($ketuaMurabahah)
            ->put("/admin/pembiayaan/validate/{$pembiayaan->id}", [
                'status' => FinancingReqStatusEnum::REJECTED->value,
                'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
            ]);

        $this->assertDatabaseHas('financing_verifications', [
            'pembiayaan_id' => $pembiayaan->id,
            'final_verification_status' => FinancingReqStatusEnum::REJECTED->value,
            'notes' => 'Penghasilan bersih tidak mencukupi untuk bayar angsuran.',
        ]);
    });

    it('Ketua koperasi dapat menyetujui permohonan pembiayaan yang diajukan oleh ketua murabahah', function () {
        $ketuaKoperasi = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaKoperasi->syncRoles('Ketua');

        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahahMember = Anggota::factory()->create(['pengguna_id' => $ketuaMurabahah->id, 'status' => MemberStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $ketuaMurabahahMember->id,
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
        ]);

        $response = $this->actingAs($ketuaKoperasi)
            ->put("/admin/pembiayaan/validate/{$pembiayaan->id}", [
                'status' => 'Disetujui',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('financing_verifications', [
            'pembiayaan_id' => $pembiayaan->id,
            'final_verification_status' => 'Disetujui',
        ]);
    });
});

describe('Aplikasi harus menyediakan daftar pembiayaan murabahah untuk ketua koperasi, ketua murabahah, dan staf murabahah.', function () {
    it('Ketua Murabahah dapat melihat daftar pembiayaan aktif dan riwayat semua anggota', function () {
        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');
        Pembiayaan::factory()->count(3)->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $responseActive = $this->actingAs($ketuaMurabahah)->get('/admin/pembiayaan');
        $responseActive->assertStatus(200);
    });

    it('Selain pengurus terkait tidak dapat mengakses daftar pembiayaan', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Sekretaris');

        $response = $this->actingAs($user)->get('/admin/pembiayaan');
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan detail pembiayaan murabahah yang memuat riwayat pembayaran.', function () {
    it('Ketua Murabahah dapat melihat detail pembiayaan beserta riwayat pembayaran', function () {
        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');
        $pembiayaan = Pembiayaan::factory()->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $response = $this->actingAs($ketuaMurabahah)->get("/admin/pembiayaan/show/{$pembiayaan->id}");
        $response->assertStatus(200);
    });

    it('Selain pengurus terkait tidak dapat mengakses detail pembiayaan', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Sekretaris');
        $pembiayaan = Pembiayaan::factory()->create(['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value]);

        $response = $this->actingAs($user)->get("/admin/pembiayaan/show/{$pembiayaan->id}");
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus menyediakan informasi pembiayaan murabahah yang masih berjalan dan selesai bagi anggota.', function () {
    it('Anggota dapat melihat pembiayaan yang masih berjalan dan selesai', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);

        $response = $this->actingAs($user)->get('/user/pembiayaan');
        $response2 = $this->actingAs($user)->get("/user/pembiayaan/show/{$pembiayaan->id}");
        $response->assertStatus(200);
        $response2->assertStatus(200);
    });

    it('Anggota tidak dapat melihat pembiayaan anggota lain', function () {
        $member1 = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user1 = Pengguna::where('id', $member1->pengguna_id)->first();
        $user1->syncRoles('Anggota');

        $member2 = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $financing2 = Pembiayaan::factory()->create([
            'anggota_id' => $member2->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $response = $this->actingAs($user1)->get("/user/pembiayaan/show/{$financing2->id}");
        $response->assertStatus(403);
    });
});

describe('Aplikasi harus dapat mengirimkan notifikasi jatuh tempo pembayaran angsuran kepada masing-masing anggota maksimal H-1 jatuh tempo pembayaran melalui aplikasi.', function () {
    it('Sistem mengirimkan notifikasi H-1 sebelum jatuh tempo pembayaran angsuran', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $this->artisan('notifications:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'anggota_id' => $anggota->id,
        ]);
    });
});

describe('Aplikasi harus menyediakan pemantauan notifikasi koperasi oleh penanggung jawab anggota', function () {
    it('Penanggung Jawab Anggota dapat melihat notifikasi terkait pembiayaan anggota', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $pjAnggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pjAnggota->syncRoles('Penanggung Jawab Anggota');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $this->artisan('notifications:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'anggota_id' => $anggota->id,
        ]);

        $response = $this->actingAs($pjAnggota)->get('/admin/notifications');
        $response->assertStatus(200);
    });
});

describe('Aplikasi harus dapat menyediakan pencatatan transaksi pembayaran angsuran piutang murabahah oleh staf murabahah.', function () {
    it('Staf Murabahah dapat mencatat pembayaran angsuran', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');
        $pembiayaan = Pembiayaan::factory()->create(['status' => 'Angsuran Berjalan']);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
            'tenor' => 12,
            'metode_pembayaran' => \App\Enums\FinancingPaymentMethodEnum::INSTALLMENT->value,
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/store", [
                'angsuran_id' => $angsuran->id,
                'pembiayaan_id' => $pembiayaan->id,
                'jumlah_angsuran_dibayar' => 1833333,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('pembayaran_angsuran', [
            'angsuran_id' => $angsuran->id,
            'jumlah_angsuran_dibayar' => 1833333,
        ]);
    });

    it('Selain Staf Murabahah tidak dapat mencatat pembayaran angsuran', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Ketua Murabahah');
        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);
        $response = $this->actingAs($user)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/store", [
                'angsuran_id' => $angsuran->id,
                'pembiayaan_id' => $pembiayaan->id,
                'jumlah_angsuran_dibayar' => 1833333,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);

        $response->assertStatus(403);
    });
});

describe('Aplikasi harus dapat menyediakan penjadwalan ulang pembayaran angsuran pembiayaan oleh staf murabahah', function () {
    it('Staf Murabahah dapat menjadwalkan ulang pembayaran angsuran', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/reschedule", [
                'angsuran_id' => $angsuran->id,
                'tgl_jatuh_tempo' => now()->addDays(7)->format('Y-m-d'),
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('angsuran', [
            'id' => $angsuran->id,
            'tgl_jatuh_tempo' => now()->addDays(7)->format('Y-m-d'),
        ]);
    });
});

describe('Aplikasi harus menyediakan pencatatan permohonan pelunasan sebelum jatuh tempo dari anggota oleh staf murabahah.', function () {
    it('Staf Murabahah dapat memproses permohonan pelunasan sebelum jatuh tempo', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        ObjekPembiayaan::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => 'Motor Honda',
            'harga_beli_per_unit' => 50000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/pembiayaan/repayment", [
                'method' => 'Tunai',
                'angsuran_id' => $angsuran->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pembiayaan', [
            'id' => $pembiayaan->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

    it('Selain Staf Murabahah tidak dapat memproses permohonan pelunasan sebelum jatuh tempo', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Ketua Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
        ]);

        ObjekPembiayaan::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => 'Motor Honda',
            'harga_beli_per_unit' => 50000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user)
            ->post("/admin/pembiayaan/repayment", [
                'method' => 'Tunai',
                'angsuran_id' => $angsuran->id,
            ]);

        $response->assertStatus(403);
    });
});

describe('Dapat memetakan seluruh kolektibilitas pembiayaan dengan akurat', function () {
    it('Sistem dapat memetakan 4 data pembiayaan (Lancar, Kurang Lancar, Diragukan, Macet)', function () {
        // kunci waktu ke 26 Juni 2026 biar tesnya nggak basi
        $this->travelTo(\Carbon\Carbon::parse('2026-06-26 12:00:00'));

        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        // bikin data pembiayaan yang lancar (belum jatuh tempo cicilannya)
        $finLancar = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => '2026-05-01',
            'tenor' => 12,
        ]);
        Angsuran::factory()->create([
            'pembiayaan_id' => $finLancar->id,
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            'tgl_jatuh_tempo' => '2026-07-26',
        ]);

        // bikin data kurang lancar: ceritanya dia nunggak 5 bulan tapi akadnya masih jalan
        $finKurangLancar = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => '2025-12-01',
            'tenor' => 12,
        ]);
        Angsuran::factory()->create([
            'pembiayaan_id' => $finKurangLancar->id,
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            'tgl_jatuh_tempo' => '2026-01-26',
        ]);

        // bikin data diragukan: nunggaknya udah 8 bulan
        $finDiragukan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => '2025-09-01',
            'tenor' => 12,
        ]);
        Angsuran::factory()->create([
            'pembiayaan_id' => $finDiragukan->id,
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            'tgl_jatuh_tempo' => '2025-10-26',
        ]);

        // bikin data macet: kontraknya udah expired dari akhir tahun kemaren (Desember 2025)
        $finMacet = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => '2024-12-01',
            'tenor' => 12,
        ]);
        Angsuran::factory()->create([
            'pembiayaan_id' => $finMacet->id,
            'status' => \App\Enums\InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            'tgl_jatuh_tempo' => '2025-11-26',
        ]);

        $dasborService = app(\App\Services\Admin\DasborService::class);
        $petaPembiayaan = $dasborService->getPetaPembiayaan('2026-06-26 23:59:59');

        $this->assertEquals([
            'Lancar' => 1,
            'Kurang Lancar' => 1,
            'Diragukan' => 1,
            'Macet' => 1,
        ], $petaPembiayaan);

        $this->travelBack();
    });
});

describe('Aplikasi harus dapat menghitung poin anggota dari pembayaran margin pembiayaan berdasarkan periode buku secara otomatis.', function () {
    it('Menghitung poin anggota dari pembayaran margin pembiayaan berdasarkan periode buku secara otomatis.', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        GlobalSetting::where('key', 'status_tutup_buku')->update(['value' => 'closed']);
        GlobalSetting::where('key', 'tanggal_awal_periode')->update(['value' => '2026-01-01']);
        GlobalSetting::where('key', 'tanggal_akhir_periode')->update(['value' => '2026-12-31']);
        GlobalSetting::where('key', 'murabaha_point_amount')->update(['value' => '100000']);
        GlobalSetting::where('key', 'murabaha_point_reward')->update(['value' => '1']);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
        ]);

        PembayaranAngsuran::factory()->create([
            'angsuran_id' => $angsuran->id,
            'margin_dibayar' => 150000,
            'pokok_dibayar' => 0,
            'jumlah_angsuran_dibayar' => 150000,
            'tgl_pembayaran' => '2026-06-15',
        ]);

        $this->travelTo(\Carbon\Carbon::parse('2026-12-31'));

        $this->artisan('points:calculate-murabahah-points')
            ->assertSuccessful();

        $this->assertDatabaseHas('poin', [
            'pengguna_id' => $user->id,
            'jml_perolehan' => 1,
        ]);

        $this->travelBack();
    });

    it('Tidak menghitung poin anggota dari pembayaran margin pembiayaan kurang dari threshold', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->syncRoles('Anggota');
        $anggota = Anggota::factory([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ])->create();

        GlobalSetting::where('key', 'status_tutup_buku')->update(['value' => 'closed']);
        GlobalSetting::where('key', 'tanggal_awal_periode')->update(['value' => '2026-01-01']);
        GlobalSetting::where('key', 'tanggal_akhir_periode')->update(['value' => '2026-12-31']);
        GlobalSetting::where('key', 'murabaha_point_amount')->update(['value' => '100000']);
        GlobalSetting::where('key', 'murabaha_point_reward')->update(['value' => '1']);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
        ]);

        PembayaranAngsuran::factory()->create([
            'angsuran_id' => $angsuran->id,
            'margin_dibayar' => 50000,
            'pokok_dibayar' => 0,
            'jumlah_angsuran_dibayar' => 50000,
            'tgl_pembayaran' => '2026-06-15',
        ]);

        $this->travelTo(\Carbon\Carbon::parse('2026-12-31'));

        $this->artisan('points:calculate-murabahah-points')
            ->assertSuccessful();

        $this->assertDatabaseMissing('poin', [
            'pengguna_id' => $user->id,
        ]);

        $this->travelBack();
    });
});

    it('Bukti transaksi berupa file PDF dihasilkan setelah transaksi pembayaran angsuran berhasil dicatat', function () {
        Storage::fake('public');

        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(11),
            'tenor' => 12,
            'metode_pembayaran' => \App\Enums\FinancingPaymentMethodEnum::INSTALLMENT->value,
        ]);

        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran' => 1833333,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => 'Terjadwal',
        ]);

        $response = $this->actingAs($staffMurabahah)
            ->post("/admin/pembiayaan/{$pembiayaan->id}/payments/store", [
                'angsuran_id' => $angsuran->id,
                'pembiayaan_id' => $pembiayaan->id,
                'jumlah_angsuran_dibayar' => 1833333,
                'tgl_pembayaran' => now()->format('Y-m-d'),
                'metode_pembayaran' => 'Tunai',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        
        $response->assertSessionHas('pdf_url');

        $files = Storage::disk('public')->allFiles('receipts/' . $anggota->id);
        expect($files)->not->toBeEmpty();
    });