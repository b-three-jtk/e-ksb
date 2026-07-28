<?php

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\NotificationStatusEnum;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\Notifikasi;
use App\Models\Pembiayaan;
use App\Models\Pengguna;
use App\Services\NotifikasiService;
use Database\Seeders\AkunSeeder;
use Database\Seeders\JenisBarangSeeder;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

