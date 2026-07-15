<?php

namespace Database\Seeders;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\AhliWaris;
use App\Models\Anggota;
use App\Models\Angsuran;
use App\Models\DetailJurnal;
use App\Models\JenisBarang;
use App\Models\Jurnal;
use App\Models\ObjekPembiayaan;
use App\Models\PembayaranAngsuran;
use App\Models\Pembiayaan;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        // Ambil semua anggota
        $anggota = Anggota::all();

        if ($anggota->isEmpty()) {
            return; // Skip jika tidak ada anggota
        }

        // Mapping skenario
        $scenarios = [
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value],
            ['status' => FinancingReqStatusEnum::PENDING_REVIEW->value],
            ['status' => FinancingReqStatusEnum::PAID->value],
            ['status' => FinancingReqStatusEnum::APPROVED->value],
            ['status' => FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value],
            ['status' => FinancingReqStatusEnum::REJECTED->value],
            ['status' => FinancingReqStatusEnum::WAITING_DOCUMENTS->value],
            ['status' => FinancingReqStatusEnum::TANGGUH->value],
        ];

        $items = [
            ['nama_barang' => 'Kipas Angin Miyako', 'spec' => 'Kipas Angin', 'price' => 300000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Smartphone Samsung Galaxy A05', 'spec' => 'Samsung Galaxy A05', 'price' => 1500000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Motor Honda Vario 160', 'spec' => 'Motor Honda Vario 160cc Tahun 2024', 'price' => 50000000, 'type' => 'Kendaraan Roda Dua'],
            ['nama_barang' => 'Laptop ASUS VivoBook', 'spec' => 'Laptop ASUS VivoBook 15, Intel i5, RAM 8GB', 'price' => 30000000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Mesin Jahit Singer', 'spec' => 'Mesin Jahit Singer Portable, Semi Otomatis', 'price' => 2000000, 'type' => 'Peralatan Usaha'],
        ];

        // Generate 200 pembiayaan yang bervariasi agar grafiknya penuh 5 tahun terakhir
        for ($j = 0; $j < 200; $j++) {
            $memberIndex = $j % $anggota->count();
            $currentAnggota = $anggota[$memberIndex];

            $scenarioIndex = $j % count($scenarios);
            $scenario = $scenarios[$scenarioIndex];

            $itemIndex = $j % count($items);
            $item = $items[$itemIndex];

            // Pastikan tidak ada kewajiban yang aktif ganda untuk satu anggota
            if (in_array($scenario['status'], [
                FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                FinancingReqStatusEnum::TANGGUH->value,
            ])) {
                $hasActive = Pembiayaan::where('anggota_id', $currentAnggota->id)
                    ->whereIn('status', [
                        FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                        FinancingReqStatusEnum::TANGGUH->value,
                    ])->exists();
                
                if ($hasActive) {
                    $scenario['status'] = FinancingReqStatusEnum::PAID->value;
                }
            }

            // Pastikan Anggota memiliki data Pekerjaan
            if (!$currentAnggota->pekerjaanAnggota()->exists()) {
                \App\Models\PekerjaanAnggota::create([
                    'anggota_id' => $currentAnggota->id,
                    'status_pekerjaan' => 'Karyawan Swasta',
                    'jabatan_pekerjaan' => 'Staff',
                    'nama_perusahaan' => 'PT Makmur Sejahtera',
                    'bidang_usaha' => 'Perdagangan',
                    'lama_bekerja' => rand(1, 10),
                    'alamat_tempat_bekerja' => 'Jl. Sudirman No. 123, Jakarta',
                    'no_telp_kantor' => '021' . rand(1000000, 9999999),
                ]);
            }

            // Pastikan Anggota memiliki data Keuangan
            if (!$currentAnggota->keuanganAnggota()->exists()) {
                \App\Models\KeuanganAnggota::create([
                    'anggota_id' => $currentAnggota->id,
                    'jml_gaji_pokok' => rand(5000000, 15000000),
                    'jml_penghasilan_usaha' => rand(0, 5000000),
                    'jml_penghasilan_pasangan' => 0,
                    'jml_penghasilan_lainnya' => 0,
                    'jml_biaya_hidup_keluarga' => rand(2000000, 5000000),
                    'jml_biaya_pendidikan' => rand(500000, 2000000),
                    'jml_cicilan' => rand(0, 1000000),
                    'jml_biaya_lainnya' => 0,
                ]);
            }

            // Pastikan Anggota memiliki setidaknya 1 Ahli Waris (Wajib untuk pembiayaan)
            if ($currentAnggota->ahliWaris()->count() === 0) {
                $ahliWaris = AhliWaris::create([
                    'nik_ahli_waris' => (string) mt_rand(1000000000000000, 9999999999999999),
                    'nama_ahli_waris' => 'Ahli Waris ' . ($currentAnggota->user->nama ?? 'Anggota'),
                    'kontak_ahli_waris' => '08' . mt_rand(100000000, 999999999),
                ]);
                $currentAnggota->ahliWaris()->attach($ahliWaris->nik_ahli_waris, ['hubungan' => \App\Enums\AhliWarisEnum::SON->value]);
            }

            if ($scenario['status'] === FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value) {
                $this->seedActiveFinancing($currentAnggota, $item);
            } elseif ($scenario['status'] === FinancingReqStatusEnum::PAID->value) {
                $this->seedCompletedFinancing($currentAnggota, $item);
            } else {
                $this->seedPendingFinancing($currentAnggota, $item, $scenario['status']);
            }
        }

        // PENYESUAIAN RUMUS DASHBOARD: KAS (101) JADI 70M, PIUTANG (104) JADI 150M
        $admin = Pengguna::first();
        $date = now()->endOfDay();
        
        $kasBalance = DetailJurnal::where('no_ref_akun', '101')
            ->selectRaw("SUM(CASE WHEN posisi_akun = 'Debit' THEN nominal ELSE -nominal END) as total")
            ->value('total') ?? 0;
            
        $piutangBalance = DetailJurnal::where('no_ref_akun', '104')
            ->selectRaw("SUM(CASE WHEN posisi_akun = 'Debit' THEN nominal ELSE -nominal END) as total")
            ->value('total') ?? 0;

        $groupId = Str::uuid();
        
        Jurnal::create([
            'id' => $groupId,
            'tgl_transaksi' => $date,
            'created_by' => $admin->id,
        ]);
        
        // Sesuaikan Kas ke 70M
        if ($kasBalance > 70000000) {
            $diffKas = $kasBalance - 70000000;
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '102', 'posisi_akun' => 'Debit', 'nominal' => $diffKas, 'updated_by' => $admin->id]);
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '101', 'posisi_akun' => 'Credit', 'nominal' => $diffKas, 'updated_by' => $admin->id]);
        } elseif ($kasBalance < 70000000) {
            $diffKas = 70000000 - $kasBalance;
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '101', 'posisi_akun' => 'Debit', 'nominal' => $diffKas, 'updated_by' => $admin->id]);
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '102', 'posisi_akun' => 'Credit', 'nominal' => $diffKas, 'updated_by' => $admin->id]);
        }

        // Sesuaikan Piutang ke 150M
        if ($piutangBalance > 150000000) {
            $diffPiutang = $piutangBalance - 150000000;
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '102', 'posisi_akun' => 'Debit', 'nominal' => $diffPiutang, 'updated_by' => $admin->id]);
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '104', 'posisi_akun' => 'Credit', 'nominal' => $diffPiutang, 'updated_by' => $admin->id]);
        } elseif ($piutangBalance < 150000000) {
            $diffPiutang = 150000000 - $piutangBalance;
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '104', 'posisi_akun' => 'Debit', 'nominal' => $diffPiutang, 'updated_by' => $admin->id]);
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '102', 'posisi_akun' => 'Credit', 'nominal' => $diffPiutang, 'updated_by' => $admin->id]);
        }

        // Sesuaikan Dana Alokasi Pembiayaan (102) agar tidak negatif (set ke 2 Miliar)
        $alokasiBalance = DetailJurnal::where('no_ref_akun', '102')
            ->selectRaw("SUM(CASE WHEN posisi_akun = 'Debit' THEN nominal ELSE -nominal END) as total")
            ->value('total') ?? 0;
            
        if ($alokasiBalance < 2000000000) {
            $diffAlokasi = 2000000000 - $alokasiBalance;
            DetailJurnal::create(['jurnal_id' => $groupId, 'no_ref_akun' => '102', 'posisi_akun' => 'Debit', 'nominal' => $diffAlokasi, 'updated_by' => $admin->id]);
            // Hanya satu sisi (Debit) karena kita tidak ingin menambah ke Simpanan/COA lain
        }

        // --- SEED TRANSAKSI ANGSURAN MENUNGGU VERIFIKASI ---
        $pendingAngsurans = Angsuran::where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->where('tgl_jatuh_tempo', '<=', now())
            ->take(3)
            ->get();

        foreach ($pendingAngsurans as $idx => $angsuran) {
            $pembiayaan = $angsuran->pembiayaan;
            $monthlyMargin = round($pembiayaan->margin_keuntungan / $pembiayaan->tenor, 2);
            $monthlyCostPrice = round(($pembiayaan->harga_perolehan - $pembiayaan->uang_muka) / $pembiayaan->tenor, 2);
            
            PembayaranAngsuran::create([
                'kode_transaksi_pembayaran' => $this->getUniqueTransCode(),
                'angsuran_id' => $angsuran->id,
                'jumlah_angsuran_dibayar' => $angsuran->nominal_angsuran,
                'pokok_dibayar' => $monthlyCostPrice,
                'margin_dibayar' => $monthlyMargin,
                'metode_pembayaran' => PaymentMethodsEnum::CASHLESS->value,
                'is_pelunasan_lebih_cepat' => false,
                'tgl_pembayaran' => now(),
                'status' => 'Menunggu Verifikasi',
                'updated_by' => $admin->id,
            ]);
        }
    }

    private function getUniqueTransCode(): string
    {
        return 'TP' . str_pad(self::$transCodeCounter++, 8, '0', STR_PAD_LEFT);
    }

    private function seedActiveFinancing(Anggota $anggota, array $item): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Administrator Sistem'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.2); // 20% margin
        $downPayment = (int)($item['price'] * 0.1); // 10% DP

        // Atur agar tenor tidak menyeberang tahun (selalu habis di tahun yang sama dengan akad)
        // Karena statusnya masih berjalan (Active), maka akad harus di tahun ini
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Pilih bulan akad sebelum bulan ini agar ada cicilan yang sudah lewat
        $akadMonth = rand(1, max(1, $currentMonth - 1));
        
        $monthsPassed = $currentMonth - $akadMonth;
        
        $minTenor = $monthsPassed + 1; // Agar masih ada sisa cicilan (belum lunas)
        $maxTenor = 12 - $akadMonth; // Agar tidak nyebrang tahun
        
        if ($minTenor > $maxTenor) {
            $tenor = $maxTenor;
        } else {
            $tenor = rand($minTenor, $maxTenor);
        }
        
        $akadDate = Carbon::createFromDate($currentYear, $akadMonth, rand(1, 28))->startOfDay();

        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
            'harga_perkiraan' => $item['price'] + $margin,
            'harga_perolehan' => $item['price'],
            'margin_keuntungan' => $margin,
            'uang_muka' => $downPayment,
            'tgl_akad' => $akadDate,
            'status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
            'metode_pembayaran' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin?->id,
            'tenor' => $tenor,
        ]);

        // Create Pembiayaan Item
        ObjekPembiayaan::create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => $item['nama_barang'],
            'spesifikasi_barang' => $item['spec'],
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'harga_beli_per_unit' => $item['price'],
            'jenis_barang_id' => JenisBarang::where('nama_jenis_barang', $item['type'])->first()?->id,
        ]);

        $akadJournal = Jurnal::create([
            'tgl_transaksi' => $akadDate,
            'created_by' => $admin?->id,
        ]);
        
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'posisi_akun' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '102', // Dana Alokasi Pembiayaan
            'posisi_akun' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);

        if ($downPayment > 0) {
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '101', // Kas
                'posisi_akun' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'posisi_akun' => 'Credit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
        }

        $piutangPokok = $pembiayaan->harga_perolehan - $downPayment;
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '104', // Piutang Murabahah
            'posisi_akun' => 'Debit',
            'nominal' => $piutangPokok,
            'updated_by' => $admin?->id,
        ]);
        if ($downPayment > 0) {
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'posisi_akun' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
        }
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'posisi_akun' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);

        // Hitung cicilan yang sudah jatuh tempo
        $pastInstallments = 0;
        for ($k = 1; $k <= $tenor; $k++) {
            if ($akadDate->copy()->addMonths($k)->isPast()) {
                $pastInstallments++;
            }
        }
        
        // 80% lancar, 20% menunggak 1-3 bulan terakhir
        $unpaidMonths = (rand(1, 100) <= 80) ? 0 : rand(1, min(3, max(1, $pastInstallments)));
        $paidInstallments = max(0, $pastInstallments - $unpaidMonths);

        for ($i = 1; $i <= $tenor; $i++) {
            $monthlyMargin = round($pembiayaan->margin_keuntungan / $tenor, 2);
            $monthlyCostPrice = round(($pembiayaan->harga_perolehan - $pembiayaan->uang_muka) / $tenor, 2);
            $monthlyPayment = $monthlyCostPrice + $monthlyMargin;
            $dueDate = $akadDate->copy()->addMonths($i);

            // Tentukan status pembayaran cicilan (berurutan)
            $isPaid = $i <= $paidInstallments;

            $angsuran = Angsuran::create([
                'pembiayaan_id' => $pembiayaan->id,
                'angsuran_ke' => $i,
                'tgl_jatuh_tempo' => $dueDate,
                'nominal_angsuran' => $monthlyPayment,
                'status' => $isPaid ? InstallmentPaymentScheduleStatusEnum::PAID->value : InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);

            // Jika dibayar, buat history transaksinya
            if ($isPaid) {
                PembayaranAngsuran::create([
                    'kode_transaksi_pembayaran' => $this->getUniqueTransCode(),
                    'angsuran_id' => $angsuran->id,
                    'jumlah_angsuran_dibayar' => $monthlyPayment,
                    'pokok_dibayar' => $monthlyCostPrice,
                    'margin_dibayar' => $monthlyMargin,
                    'metode_pembayaran' => PaymentMethodsEnum::CASHLESS->value,
                    'is_pelunasan_lebih_cepat' => false,
                    'tgl_pembayaran' => $dueDate,
                    'updated_by' => $admin?->id,
                ]);

                $journal = Jurnal::create([
                    'tgl_transaksi' => $dueDate,
                    'created_by' => $admin?->id,
                ]);

                DetailJurnal::create([
                    'jurnal_id' => $journal->id,
                    'no_ref_akun' => '101',
                    'posisi_akun' => 'Debit',
                    'nominal' => $monthlyPayment,
                    'updated_by' => $admin?->id,
                ]);

                DetailJurnal::create([
                    'jurnal_id' => $journal->id,
                    'no_ref_akun' => '104', // Piutang Murabahah
                    'posisi_akun' => 'Credit',
                    'nominal' => $monthlyCostPrice,
                    'updated_by' => $admin?->id,
                ]);

                DetailJurnal::create([
                    'jurnal_id' => $journal->id,
                    'no_ref_akun' => '401', // Pendapatan Margin
                    'posisi_akun' => 'Credit',
                    'nominal' => $monthlyMargin,
                    'updated_by' => $admin?->id,
                ]);
            }
        }
    }

    private function seedPendingFinancing(Anggota $anggota, array $item, string $status): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);

        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
            'harga_perkiraan' => $item['price'],
            'harga_perolehan' => $item['price'],
            'margin_keuntungan' => $margin,
            'uang_muka' => $downPayment,
            'tgl_permohonan' => now(),
            'status' => $status,
            'metode_pembayaran' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin?->id,
        ]);

        // Create Pembiayaan Item
        ObjekPembiayaan::create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => $item['nama_barang'],
            'spesifikasi_barang' => $item['spec'],
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'harga_beli_per_unit' => $item['price'],
            'jenis_barang_id' => JenisBarang::where('nama_jenis_barang', $item['type'])->first()?->id,
        ]);

        $journal = Jurnal::create([
            'tgl_transaksi' => now(),
            'created_by' => $admin?->id,
        ]);

        DetailJurnal::create([
            'jurnal_id' => $journal->id,
            'no_ref_akun' => '103',
            'posisi_akun' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);

        DetailJurnal::create([
            'jurnal_id' => $journal->id,
            'no_ref_akun' => '102',
            'posisi_akun' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);
    }

    private function seedCompletedFinancing(Anggota $anggota, array $item): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);
        // Untuk pembiayaan Lunas, cari bulan dan tahun yang tidak menyeberang tahun
        $year = now()->year - rand(0, 4);
        $akadMonth = rand(1, 10); // Maks bulan 10 agar ada ruang tenor
        $maxTenor = 12 - $akadMonth;
        
        $tenor = rand(2, $maxTenor); // Minimal 2 bulan, maksimal sampai Desember tahun yang sama

        $akadDate = Carbon::createFromDate($year, $akadMonth, rand(1, 28))->startOfDay();
        
        // Jika ternyata tanggal lunasnya (akad + tenor) melewati hari ini, mundurkan tahunnya
        if ($akadDate->copy()->addMonths($tenor)->isFuture()) {
            $akadDate->subYear();
        }
        
        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
            'harga_perkiraan' => $item['price'] + $margin,
            'harga_perolehan' => $item['price'],
            'margin_keuntungan' => $margin,
            'uang_muka' => $downPayment,
            'tgl_akad' => $akadDate,
            'tgl_lunas' => $akadDate->copy()->addMonths($tenor),
            'status' => FinancingReqStatusEnum::PAID->value,
            'metode_pembayaran' => FinancingPaymentMethodEnum::INSTALLMENT->value,
            'updated_by' => $admin?->id,
        ]);

        // Create Pembiayaan Item
        ObjekPembiayaan::create([
            'pembiayaan_id' => $pembiayaan->id,
            'nama_barang' => $item['nama_barang'],
            'spesifikasi_barang' => $item['spec'],
            'kuantitas' => 1,
            'kondisi_produk' => 'Baru',
            'harga_beli_per_unit' => $item['price'],
            'jenis_barang_id' => JenisBarang::where('nama_jenis_barang', $item['type'])->first()?->id,
        ]);

        $akadDate = Carbon::parse($pembiayaan->tgl_akad);
        $akadJournal = Jurnal::create([
            'tgl_transaksi' => $akadDate,
            'created_by' => $admin?->id,
        ]);
        
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'posisi_akun' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '102', // Dana Alokasi Pembiayaan
            'posisi_akun' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);

        if ($downPayment > 0) {
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '101', // Kas
                'posisi_akun' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'posisi_akun' => 'Credit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
        }

        $piutangPokok = $pembiayaan->harga_perolehan - $downPayment;
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '104', // Piutang Murabahah
            'posisi_akun' => 'Debit',
            'nominal' => $piutangPokok,
            'updated_by' => $admin?->id,
        ]);
        if ($downPayment > 0) {
            DetailJurnal::create([
                'jurnal_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'posisi_akun' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
            ]);
        }
        DetailJurnal::create([
            'jurnal_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'posisi_akun' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
        ]);

        for ($i = 1; $i <= $tenor; $i++) {
            $monthlyMargin = round($pembiayaan->margin_keuntungan / $tenor, 2);
            $monthlyCostPrice = round(($pembiayaan->harga_perolehan - $pembiayaan->uang_muka) / $tenor, 2);
            $monthlyPayment = $monthlyCostPrice + $monthlyMargin;

            $akadDate = Carbon::parse($pembiayaan->tgl_akad);
            $dueDate = $akadDate->copy()->addMonths($i);

            $angsuran = Angsuran::create([
                'pembiayaan_id' => $pembiayaan->id,
                'angsuran_ke' => $i,
                'tgl_jatuh_tempo' => $dueDate,
                'nominal_angsuran' => $monthlyPayment,
                'status' => InstallmentPaymentScheduleStatusEnum::PAID->value,
            ]);

            PembayaranAngsuran::create([
                'kode_transaksi_pembayaran' => $this->getUniqueTransCode(),
                'angsuran_id' => $angsuran->id,
                'jumlah_angsuran_dibayar' => $monthlyPayment,
                'pokok_dibayar' => $monthlyCostPrice,
                'margin_dibayar' => $monthlyMargin,
                'metode_pembayaran' => PaymentMethodsEnum::CASHLESS->value,
                'is_pelunasan_lebih_cepat' => false,
                'tgl_pembayaran' => $dueDate,
                'updated_by' => $admin?->id,
            ]);

            $journal = Jurnal::create([
                'tgl_transaksi' => $dueDate,
                'created_by' => $admin?->id,
            ]);

            // kas
            DetailJurnal::create([
                'jurnal_id' => $journal->id,
                'no_ref_akun' => '101',
                'posisi_akun' => 'Debit',
                'nominal' => $monthlyPayment,
                'updated_by' => $admin?->id,
            ]);

            // piutang murabahah
            DetailJurnal::create([
                'jurnal_id' => $journal->id,
                'no_ref_akun' => '104',
                'posisi_akun' => 'Credit',
                'nominal' => $monthlyCostPrice,
                'updated_by' => $admin?->id,
            ]);

            // pendapatan margin murabahah
            DetailJurnal::create([
                'jurnal_id' => $journal->id,
                'no_ref_akun' => '401',
                'posisi_akun' => 'Credit',
                'nominal' => $monthlyMargin,
                'updated_by' => $admin?->id,
            ]);
        }
    }
}
