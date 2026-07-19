<?php

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Akun;
use App\Models\AkunSimpanan;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\DetailJurnal;
use App\Models\JenisBarang;
use App\Models\Jurnal;
use App\Models\Notifikasi;
use App\Models\ObjekPembiayaan;
use App\Models\Pemasok;
use App\Models\PembayaranAngsuran;
use App\Models\Pembiayaan;
use App\Models\PengaturanUmum;
use App\Models\Pengguna;
use App\Models\TransaksiSimpanan;
use App\Models\Wakalah;
use App\Services\NotifikasiService;
use Database\Seeders\AkunSeeder;
use Database\Seeders\JenisBarangSeeder;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(AkunSeeder::class);
    $this->seed(PengaturanUmumSeeder::class);
    $this->seed(JenisBarangSeeder::class);
});

// ============================================================================
// 1. Pembaruan Status Pengiriman Notifikasi
//    Aplikasi harus memperbarui status pengiriman notifikasi secara otomatis
//    menjadi Draf, Terkirim, atau Gagal Kirim setelah proses pengiriman dieksekusi.
// ============================================================================
describe('Aplikasi harus memperbarui status pengiriman notifikasi secara otomatis menjadi Draf, Terkirim, atau Gagal Kirim setelah proses pengiriman dieksekusi.', function () {

    it('Status notifikasi otomatis berubah menjadi "Draf" saat notifikasi baru dibuat sebelum pengiriman', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $notifikasi = Notifikasi::create([
            'anggota_id' => $anggota->id,
            'judul' => 'Pengingat Simpanan Wajib',
            'pesan' => 'Simpanan wajib belum dibayar.',
            'jenis_notifikasi' => 'mandatory_saving',
            'periode_notifikasi' => now()->format('Y-m'),
            'jenis_pengingat' => 'H-3',
            'status' => NotificationStatusEnum::DRAFT->value,
            'sudah_dibaca' => false,
            'dijadwalkan_pada' => now(),
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notifikasi->id,
            'status' => NotificationStatusEnum::DRAFT->value,
        ]);
    });

    it('Status notifikasi otomatis berubah menjadi "Terkirim" setelah pengiriman berhasil', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $notifikasi = Notifikasi::create([
            'anggota_id' => $anggota->id,
            'judul' => 'Pengingat Angsuran',
            'pesan' => 'Angsuran akan jatuh tempo.',
            'jenis_notifikasi' => 'angsuran',
            'periode_notifikasi' => now()->format('Y-m'),
            'jenis_pengingat' => 'H-3',
            'status' => NotificationStatusEnum::DRAFT->value,
            'sudah_dibaca' => false,
            'dijadwalkan_pada' => now(),
        ]);

        // Jalankan proses pengiriman via service
        $service = app(NotifikasiService::class);
        $service->deliverNotification($notifikasi);

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notifikasi->id,
            'status' => NotificationStatusEnum::SENT->value,
        ]);
        expect($notifikasi->fresh()->dikirim_pada)->not->toBeNull();
    });

    it('Status notifikasi otomatis berubah menjadi "Terkirim" setelah command notifikasi dijalankan', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(2),
        ]);

        Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'tgl_jatuh_tempo' => now()->addDays(3)->startOfDay(),
            'status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
        ]);

        $this->artisan('notifikasi:send-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifikasi', [
            'anggota_id' => $anggota->id,
            'status' => NotificationStatusEnum::SENT->value,
        ]);
    });
});

// ============================================================================
// 2. Pembaruan Status Keterbacaan Notifikasi
//    Aplikasi harus memperbarui status keterbacaan notifikasi dari
//    Belum Dibaca menjadi Sudah Dibaca secara otomatis saat anggota membuka pesan notifikasi tersebut.
// ============================================================================
describe('Aplikasi harus memperbarui status keterbacaan notifikasi dari Belum Dibaca menjadi Sudah Dibaca secara otomatis saat anggota membuka pesan notifikasi tersebut.', function () {

    it('Status notifikasi berubah dari "Belum Dibaca" menjadi "Sudah Dibaca" saat anggota membuka notifikasi', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $notifikasi = Notifikasi::create([
            'anggota_id' => $anggota->id,
            'judul' => 'Pengingat Angsuran #1',
            'pesan' => 'Angsuran ke-1 jatuh tempo pada tanggal 15.',
            'jenis_notifikasi' => 'angsuran',
            'periode_notifikasi' => now()->format('Y-m'),
            'jenis_pengingat' => 'H-3',
            'status' => NotificationStatusEnum::SENT->value,
            'sudah_dibaca' => false,
            'dijadwalkan_pada' => now(),
            'dikirim_pada' => now(),
        ]);

        // Pastikan awal: belum dibaca
        expect($notifikasi->sudah_dibaca)->toBeFalse();

        // Anggota membuka halaman detail notifikasi
        $response = $this->actingAs($user)->get("/user/notifikasi/{$notifikasi->id}");
        $response->assertStatus(200);

        // Pastikan status berubah menjadi sudah dibaca
        $this->assertDatabaseHas('notifikasi', [
            'id' => $notifikasi->id,
            'sudah_dibaca' => true,
        ]);
        expect($notifikasi->fresh()->dibaca_pada)->not->toBeNull();
    });

    it('Anggota dapat menandai semua notifikasi sebagai sudah dibaca', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        // Buat beberapa notifikasi belum dibaca
        for ($i = 0; $i < 3; $i++) {
            Notifikasi::create([
                'anggota_id' => $anggota->id,
                'judul' => "Notifikasi #{$i}",
                'pesan' => "Isi pesan #{$i}",
                'jenis_notifikasi' => 'angsuran',
                'periode_notifikasi' => now()->format('Y-m'),
                'jenis_pengingat' => 'H-3',
                'status' => NotificationStatusEnum::SENT->value,
                'sudah_dibaca' => false,
                'dijadwalkan_pada' => now(),
                'dikirim_pada' => now(),
            ]);
        }

        $this->actingAs($user)->post('/user/notifikasi/mark-all-read');

        // Semua notifikasi harus sudah dibaca
        $unreadCount = Notifikasi::where('anggota_id', $anggota->id)
            ->where('sudah_dibaca', false)->count();
        expect($unreadCount)->toBe(0);
    });
});

// ============================================================================
// 3. Validasi Kelengkapan Data dan Peringatan Duplikasi Data Anggota/Pengurus
//    Aplikasi harus memvalidasi kelengkapan data dan memberikan peringatan
//    potensi duplikasi data anggota/pengurus sebelum disimpan oleh sekretaris.
// ============================================================================
describe('Aplikasi harus memvalidasi kelengkapan data dan memberikan peringatan potensi duplikasi data anggota/pengurus sebelum disimpan oleh sekretaris.', function () {

    it('Sistem menolak penyimpanan anggota baru jika data wajib tidak lengkap', function () {
        $sekretaris = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $sekretaris->assignRole('Sekretaris');

        // Kirim data tanpa field wajib (nama, nik, no_telp, alamat, pendidikan)
        $response = $this->actingAs($sekretaris)
            ->post('/admin/users/store', [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
            ]);

        $response->assertSessionHasErrors(['nama', 'nik', 'no_telp', 'alamat_domisili', 'pendidikan_terakhir']);
    });

    it('Sistem menolak penyimpanan jika NIK anggota sudah ada (duplikasi)', function () {
        $sekretaris = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $sekretaris->assignRole('Sekretaris');

        // Buat anggota pertama dengan NIK tertentu
        Pengguna::factory()->create(['nik' => '3201012345678901']);

        // Coba daftarkan anggota baru dengan NIK yang sama
        $response = $this->actingAs($sekretaris)
            ->post('/admin/users/store', [
                'nama' => 'Duplikat User',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'tgl_lahir' => '1990-01-01',
                'status_pernikahan' => 'Kawin',
                'email' => 'duplikat@example.com',
                'alamat_domisili' => 'Jl. Test No. 1',
                'pendidikan_terakhir' => 'SMA',
                'nik' => '3201012345678901',
                'no_telp' => '081234567890',
                'nik_ahli_waris' => '6543210987654321',
                'nama_ahli_waris' => 'Ahli Waris',
                'heir_hubungan' => 'Istri',
                'kontak_ahli_waris' => '081234567891',
            ]);

        $response->assertSessionHasErrors(['nik']);
        $this->assertDatabaseMissing('pengguna', ['nama' => 'Duplikat User']);
    });

    it('Sistem menolak penyimpanan pengurus jika NIK sudah terdaftar', function () {
        $sekretaris = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $sekretaris->assignRole('Sekretaris');

        // Buat pengurus pertama
        Pengguna::factory()->create(['nik' => '1111222233334444']);

        $role = \Spatie\Permission\Models\Role::where('name', 'Bendahara')->first();

        // Coba tambah pengurus baru dengan NIK yang sama
        $response = $this->actingAs($sekretaris)
            ->post('/admin/pengurus/store', [
                'nama' => 'Pengurus Duplikat',
                'email' => 'pengurus@example.com',
                'nik' => '1111222233334444',
                'no_telp' => '081234567890',
                'role_id' => $role->id,
            ]);

        // Controller menangkap duplikasi NIK sebagai exception umum
        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('pengguna', ['nama' => 'Pengurus Duplikat']);
    });
});

// ============================================================================
// 4. Menampilkan Daftar Anggota Berdasarkan Penanggung Jawab Anggota (PJA)
//    Aplikasi harus menampilkan daftar anggota yang menjadi tanggung jawab
//    masing-masing penanggung jawab anggota.
// ============================================================================
describe('Aplikasi harus menampilkan daftar anggota yang menjadi tanggung jawab masing-masing penanggung jawab anggota.', function () {

    it('PJA hanya melihat anggota yang dialokasikan kepadanya pada halaman daftar anggota', function () {
        $pja = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pja->syncRoles('Penanggung Jawab Anggota');

        // Buat 3 anggota yang dialokasikan ke PJA ini
        $anggotaMilikPja = Anggota::factory()->count(3)->create([
            'pj_anggota_id' => $pja->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        // Buat 2 anggota yang TIDAK dialokasikan ke PJA ini
        Anggota::factory()->count(2)->create([
            'pj_anggota_id' => null,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        $response = $this->actingAs($pja)->get('/admin/users');
        $response->assertStatus(200);

        // PJA hanya bisa melihat anggota miliknya
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/User/List')
                ->has('anggota.data')
        );
    });

    it('PJA hanya melihat pembiayaan dari anggota yang dialokasikan kepadanya', function () {
        $pja = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pja->syncRoles('Penanggung Jawab Anggota');

        // Anggota milik PJA
        $anggota1 = Anggota::factory()->create([
            'pj_anggota_id' => $pja->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);
        Pembiayaan::factory()->create([
            'anggota_id' => $anggota1->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        // Anggota bukan milik PJA
        $anggota2 = Anggota::factory()->create([
            'pj_anggota_id' => null,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);
        Pembiayaan::factory()->create([
            'anggota_id' => $anggota2->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        $response = $this->actingAs($pja)->get('/admin/pembiayaan');
        $response->assertStatus(200);
    });
});

// ============================================================================
// 5. Menampilkan Daftar Anggota yang Jatuh Tempo / Menunggak bagi PJA
//    Aplikasi harus menampilkan daftar anggota yang jatuh tempo atau menunggak
//    pembayaran bagi penanggung jawab anggota.
// ============================================================================
describe('Aplikasi harus menampilkan daftar anggota yang jatuh tempo atau menunggak pembayaran bagi penanggung jawab anggota.', function () {

    it('PJA dapat melihat data anggota bermasalah pada dashboard', function () {
        $pja = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pja->syncRoles('Penanggung Jawab Anggota');

        // Buat anggota milik PJA dengan tunggakan
        $anggota = Anggota::factory()->create([
            'pj_anggota_id' => $pja->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(6),
            'tenor' => 12,
        ]);

        // Buat angsuran yang sudah lewat jatuh tempo (overdue)
        Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 1,
            'tgl_jatuh_tempo' => now()->subDays(30),
            'status' => InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ]);

        // PJA mengakses dashboard => harus bisa melihat data anggota bermasalah
        $response = $this->actingAs($pja)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/Dashboard')
                ->has('stats')
        );
    });

    it('PJA hanya melihat notifikasi terkait anggota miliknya', function () {
        $pja = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $pja->syncRoles('Penanggung Jawab Anggota');

        // Anggota milik PJA
        $anggotaPja = Anggota::factory()->create([
            'pj_anggota_id' => $pja->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        Notifikasi::create([
            'anggota_id' => $anggotaPja->id,
            'judul' => 'Tunggakan Angsuran',
            'pesan' => 'Anggota ini memiliki tunggakan angsuran.',
            'jenis_notifikasi' => 'angsuran',
            'periode_notifikasi' => now()->format('Y-m'),
            'jenis_pengingat' => 'H-0',
            'status' => NotificationStatusEnum::SENT->value,
            'sudah_dibaca' => false,
            'dijadwalkan_pada' => now(),
            'dikirim_pada' => now(),
        ]);

        // Anggota bukan milik PJA
        $anggotaLain = Anggota::factory()->create([
            'pj_anggota_id' => null,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        Notifikasi::create([
            'anggota_id' => $anggotaLain->id,
            'judul' => 'Notifikasi Lain',
            'pesan' => 'Ini bukan untuk PJA ini.',
            'jenis_notifikasi' => 'angsuran',
            'periode_notifikasi' => now()->format('Y-m'),
            'jenis_pengingat' => 'H-3',
            'status' => NotificationStatusEnum::SENT->value,
            'sudah_dibaca' => false,
            'dijadwalkan_pada' => now(),
            'dikirim_pada' => now(),
        ]);

        $response = $this->actingAs($pja)->get('/admin/notifikasi');
        $response->assertStatus(200);
    });
});

// ============================================================================
// 6. Menampilkan Status Kewajiban/Tunggakan saat Input Permohonan Pembiayaan Baru
//    Aplikasi harus menampilkan status kewajiban/tunggakan anggota secara otomatis
//    saat staf murabahah menginput permohonan pembiayaan baru.
// ============================================================================
describe('Aplikasi harus menampilkan status kewajiban/tunggakan anggota secara otomatis saat staf murabahah menginput permohonan pembiayaan baru.', function () {

    it('Sistem otomatis menolak permohonan pembiayaan baru jika anggota masih memiliki pembiayaan berjalan', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        // Buat pembiayaan aktif (masih berjalan / tunggakan)
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

        // Staf mencoba input pembiayaan baru => harus gagal karena masih ada yang berjalan
        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota' => [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [[
                        'nama_ahli_waris' => 'Waris Test',
                        'nik_ahli_waris' => '1234567890654321',
                        'hubungan' => 'Istri',
                        'kontak_ahli_waris' => '081234567890'
                    ]],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Laptop',
                    'jenis_barang_id' => JenisBarang::first()->id,
                    'harga_perkiraan' => 15000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => now()->format('Y-m-d'),
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Laptop untuk kerja.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Laptop',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 10000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('buku.jpg'),
            ]);

        // Controller menangkap error dan mengembalikan pesan error di session
        $response->assertSessionHasErrors();
    });

    it('Sistem memperbolehkan permohonan pembiayaan baru jika anggota tidak memiliki tunggakan', function () {
        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'saldo' => 10000000,
            'created_at' => now()->subMonths(6),
        ]);

        // Tidak ada pembiayaan berjalan
        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/store', [
                'anggota' => [
                    'kode_pengguna' => $anggota->user->kode_pengguna,
                    'nama' => $anggota->user->nama,
                    'nik' => $anggota->user->nik,
                    'no_telp' => $anggota->user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [[
                        'nama_ahli_waris' => 'Waris Test',
                        'nik_ahli_waris' => '1234567890654321',
                        'hubungan' => 'Istri',
                        'kontak_ahli_waris' => '081234567890'
                    ]],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => JenisBarang::first()->id,
                    'harga_perkiraan' => 50000000,
                    'kuantitas' => 1,
                    'kondisi_produk' => 'Baru',
                    'tgl_akad' => now()->format('Y-m-d'),
                    'status' => 'Belum Ditinjau',
                    'spesifikasi_barang' => 'Motor Honda terbaru.',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'income_slip_file' => UploadedFile::fake()->create('slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('buku.jpg'),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('pembiayaan', [
            'anggota_id' => $anggota->id,
            'harga_perkiraan' => 50000000,
        ]);
    });
});

// ============================================================================
// 7. Perhitungan Otomatis Sisa Kewajiban saat Pelunasan Dipercepat
//    Aplikasi harus dapat menghitung secara otomatis sisa kewajiban saat
//    pelunasan dipercepat diproses.
// ============================================================================
describe('Aplikasi harus dapat menghitung secara otomatis sisa kewajiban saat pelunasan dipercepat diproses.', function () {

    it('Sistem menghitung otomatis dan melunasi pembiayaan saat pelunasan dipercepat diproses', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);
        $user = Pengguna::where('id', $anggota->pengguna_id)->first();
        $user->syncRoles('Anggota');

        $staffMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $staffMurabahah->syncRoles('Staf Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'harga_perolehan' => 50000000,
            'margin_keuntungan' => 10000000,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(6),
            'tenor' => 12,
            'metode_pembayaran' => FinancingPaymentMethodEnum::INSTALLMENT->value,
        ]);

        ObjekPembiayaan::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => 'Motor Honda',
            'harga_beli_per_unit' => 50000000,
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
        ]);

        // Buat angsuran (sisa yang belum dibayar)
        $angsuran = Angsuran::factory()->create([
            'pembiayaan_id' => $pembiayaan->id,
            'angsuran_ke' => 7,
            'nominal_angsuran' => 5000000,
            'tgl_jatuh_tempo' => now()->addDays(10)->startOfDay(),
            'status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
        ]);

        // Proses pelunasan dipercepat
        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/repayment', [
                'method' => 'Tunai',
                'angsuran_id' => $angsuran->id,
            ]);

        $response->assertStatus(302);

        // Status pembiayaan harus berubah menjadi Lunas
        $this->assertDatabaseHas('pembiayaan', [
            'id' => $pembiayaan->id,
            'status' => FinancingReqStatusEnum::PAID->value,
        ]);
    });

    it('Selain Staf Murabahah tidak dapat memproses pelunasan dipercepat', function () {
        $anggota = Anggota::factory()->create(['status' => MemberStatusEnum::ACTIVE->value]);

        $ketuaMurabahah = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketuaMurabahah->syncRoles('Ketua Murabahah');

        $pembiayaan = Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'tgl_akad' => now()->subMonths(6),
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
            'nominal_angsuran' => 5000000,
            'tgl_jatuh_tempo' => now()->addDays(10)->startOfDay(),
            'status' => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
        ]);

        $response = $this->actingAs($ketuaMurabahah)
            ->post('/admin/pembiayaan/repayment', [
                'method' => 'Tunai',
                'angsuran_id' => $angsuran->id,
            ]);

        $response->assertStatus(403);
    });
});

// ============================================================================
// 8. Penguncian Alur: Akad Wakalah Harus Diunggah Sebelum Transaksi Murabahah
//    Aplikasi harus menerapkan urutan proses (mengunci alur) agar dokumen akad
//    wakalah diunggah terlebih dahulu sebelum transaksi murabahah dapat diproses.
// ============================================================================
describe('Aplikasi harus menerapkan urutan proses (mengunci alur) agar dokumen akad wakalah diunggah terlebih dahulu sebelum transaksi murabahah dapat diproses.', function () {

    it('Finalisasi pembiayaan murabahah berhasil jika dokumen akad wakalah disertakan', function () {
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

        $danaAlokasi = Akun::where('nama_akun', 'Dana Alokasi Pembiayaan Murabahah')->first();
        $jurnal = \App\Models\Jurnal::create(['tgl_transaksi' => now()]);
        DetailJurnal::factory()->create([
            'jurnal_id' => $jurnal->id,
            'no_ref_akun' => $danaAlokasi->no_ref_akun,
            'posisi_akun' => 'Debit',
            'nominal' => 200000000,
        ]);

        // Finalisasi DENGAN file akad wakalah => harus berhasil
        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/finalize', [
                'anggota' => [
                    'kode_pengguna' => $user->kode_pengguna,
                    'nama' => $user->nama,
                    'nik' => $user->nik,
                    'no_telp' => $user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [[
                        'nama_ahli_waris' => 'Ada Wong',
                        'nik_ahli_waris' => '1234567890654321',
                        'hubungan' => 'Istri',
                        'kontak_ahli_waris' => '081234567890',
                    ]],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => JenisBarang::first()->id,
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
                    'spesifikasi_barang' => 'Pembiayaan motor Honda.',
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
                'is_wakalah' => true,
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
                'akad_wakalah_file' => UploadedFile::fake()->create('akad_wakalah.pdf'),
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        // Pastikan data wakalah tersimpan
        $this->assertDatabaseHas('pembiayaan', [
            'harga_perolehan' => 50000000,
            'margin_keuntungan' => 10000000,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);
        $this->assertDatabaseHas('wakalah', [
            'tgl_akad' => '2024-01-02',
        ]);
    });

    it('Finalisasi pembiayaan murabahah gagal jika dokumen akad wakalah tidak disertakan', function () {
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
            'nama_pemasok' => 'PT. Pemasok Test',
            'contact' => '081234567890',
            'alamat_pemasok' => 'Jl. Test No. 1',
        ]);

        // Finalisasi TANPA file akad wakalah tapi is_wakalah = true => harus gagal (validasi)
        $response = $this->actingAs($staffMurabahah)
            ->post('/admin/pembiayaan/finalize', [
                'anggota' => [
                    'kode_pengguna' => $user->kode_pengguna,
                    'nama' => $user->nama,
                    'nik' => $user->nik,
                    'no_telp' => $user->no_telp,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'ahli_waris' => [[
                        'nama_ahli_waris' => 'Ada Wong',
                        'nik_ahli_waris' => '1234567890654321',
                        'hubungan' => 'Istri',
                        'kontak_ahli_waris' => '081234567890',
                    ]],
                ],
                'pembiayaan' => [
                    'nama_barang' => 'Motor Honda',
                    'jenis_barang_id' => JenisBarang::first()->id,
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
                    'spesifikasi_barang' => 'Pembiayaan motor Honda.',
                ],
                'pemasok' => [
                    'nama_pemasok' => 'PT. Pemasok Test',
                    'contact' => '081234567890',
                    'alamat_pemasok' => 'Jl. Test No. 1',
                ],
                'jaminan' => [
                    'jenis_jaminan' => 'Motor',
                    'nama_pemilik' => 'Pemohon',
                    'nilai_perkiraan_pasar' => 30000000,
                    'lokasi_kondisi_jaminan' => 'Bandung',
                ],
                'is_wakalah' => true,
                'akad_document_file' => UploadedFile::fake()->create('akad.pdf'),
                // akad_wakalah_file SENGAJA TIDAK DISERTAKAN padahal is_wakalah = true
                'income_slip_file' => UploadedFile::fake()->create('income_slip.jpg'),
                'bank_book_file' => UploadedFile::fake()->create('bank_book.jpg'),
            ]);

        // Harus ada error validasi karena dokumen wakalah tidak diunggah (required_if:is_wakalah,true)
        $response->assertSessionHasErrors(['akad_wakalah_file']);
    });
});

// ============================================================================
// 9. Perhitungan Otomatis Total Simpanan dan Sisa Kewajiban untuk Pengunduran Diri
//    Aplikasi harus dapat menghitung secara otomatis total simpanan dan sisa
//    kewajiban anggota sebagai dasar persetujuan pengunduran diri.
// ============================================================================
describe('Aplikasi harus dapat menghitung secara otomatis total simpanan dan sisa kewajiban anggota sebagai dasar persetujuan pengunduran diri.', function () {

    it('Halaman pengajuan resign menampilkan total simpanan dan sisa kewajiban secara otomatis', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');

        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        // Buat simpanan: pokok 1jt, wajib 2jt
        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 1000000,
        ]);
        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'saldo' => 2000000,
        ]);

        // Anggota membuka halaman resign => sistem harus menampilkan total simpanan dan kewajiban
        $response = $this->actingAs($user)->get('/user/resign');

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('User/Resign/Create')
                ->has('anggota.total_saving')
                ->has('anggota.total_obligation')
        );
    });

    it('Anggota yang masih memiliki kewajiban tidak dapat mengajukan pengunduran diri', function () {
        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');

        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::ACTIVE->value,
        ]);

        // Buat pembiayaan aktif (kewajiban masih ada)
        Pembiayaan::factory()->create([
            'anggota_id' => $anggota->id,
            'harga_perolehan' => 5000000,
            'margin_keuntungan' => 1000000,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
        ]);

        // Coba submit pengunduran diri
        $response = $this->actingAs($user)
            ->post('/user/resign', [
                'document' => UploadedFile::fake()->create('resign.pdf'),
            ]);

        $response->assertSessionHasErrors([
            'resign' => 'Anda masih memiliki kewajiban finansial yang belum dilunasi. Silakan selesaikan kewajiban tersebut sebelum mengajukan pengunduran diri.'
        ]);
    });

    it('Ketua koperasi dapat melihat total simpanan dan kewajiban pada halaman validasi pengunduran diri', function () {
        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');

        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::RESIGNED_REQUESTED->value,
        ]);

        // Buat data simpanan
        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_POKOK->value,
            'saldo' => 1000000,
        ]);
        AkunSimpanan::factory()->create([
            'anggota_id' => $anggota->id,
            'jenis_simpanan' => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'saldo' => 2000000,
        ]);

        // Ketua melihat halaman validasi pengunduran diri
        $response = $this->actingAs($ketua)->get("/admin/resignations/{$user->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/User/Resignation/Validation')
                ->has('data.total_savings')
                ->has('data.total_obligations')
        );
    });

    it('Ketua koperasi dapat memvalidasi pengunduran diri dan status anggota berubah', function () {
        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->assignRole('Ketua');

        $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $user->assignRole('Anggota');

        $anggota = Anggota::factory()->create([
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::RESIGNED_REQUESTED->value,
        ]);

        $response = $this->actingAs($ketua)
            ->put("/admin/resignations/{$user->id}");

        $response->assertStatus(302);
        $this->assertDatabaseHas('anggota', [
            'pengguna_id' => $user->id,
            'status' => MemberStatusEnum::RESIGNED->value,
        ]);
        $this->assertDatabaseHas('pengguna', [
            'id' => $user->id,
            'status' => UserStatusEnum::INACTIVE->value,
        ]);
    });
});
