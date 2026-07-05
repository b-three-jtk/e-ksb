<?php

namespace Database\Seeders;

use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\PaymentMethodsEnum;
use App\Models\Pembiayaan;
use App\Models\ObjekPembiayaan;
use App\Models\Angsuran;
use App\Models\PembayaranAngsuran;
use App\Models\Jurnal;
use App\Models\JournalEntry;
use App\Models\Anggota;
use App\Models\JenisBarang;
use App\Models\Pengguna;
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

        Anggota::factory()->count(100)->create();

        // Ambil semua anggota
        $anggota = Anggota::all();

        if ($anggota->isEmpty()) {
            return; // Skip jika tidak ada anggota
        }

        // Mapping skenario
        $scenarios = [
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'lancar'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'kurang_lancar'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'diragukan'],
            ['status' => FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value, 'kolektibilitas' => 'macet'],
            ['status' => FinancingReqStatusEnum::PENDING_REVIEW->value, 'kolektibilitas' => null],
            ['status' => FinancingReqStatusEnum::PAID->value, 'kolektibilitas' => null],
        ];

        $items = [
            ['nama_barang' => 'Kipas Angin Miyako', 'spec' => 'Kipas Angin', 'price' => 300000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Smartphone Samsung Galaxy A05', 'spec' => 'Samsung Galaxy A05', 'price' => 1500000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Motor Honda Vario 160', 'spec' => 'Motor Honda Vario 160cc Tahun 2024', 'price' => 50000000, 'type' => 'Kendaraan Roda Dua'],
            ['nama_barang' => 'Laptop ASUS VivoBook', 'spec' => 'Laptop ASUS VivoBook 15, Intel i5, RAM 8GB', 'price' => 30000000, 'type' => 'Elektronik'],
            ['nama_barang' => 'Mesin Jahit Singer', 'spec' => 'Mesin Jahit Singer Portable, Semi Otomatis', 'price' => 2000000, 'type' => 'Peralatan Usaha'],
        ];

        // Generate 50 pembiayaan yang bervariasi agar grafiknya penuh
        for ($j = 0; $j < 50; $j++) {
            $memberIndex = $j % $anggota->count();
            $currentAnggota = $anggota[$memberIndex];

            $scenarioIndex = $j % count($scenarios);
            $scenario = $scenarios[$scenarioIndex];

            $itemIndex = $j % count($items);
            $item = $items[$itemIndex];

            if ($scenario['status'] === FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value) {
                $this->seedActiveFinancing($currentAnggota, $item, $scenario['kolektibilitas']);
            } elseif ($scenario['status'] === FinancingReqStatusEnum::PENDING_REVIEW->value) {
                $this->seedPendingFinancing($currentAnggota, $item);
            } else {
                $this->seedCompletedFinancing($currentAnggota, $item);
            }
        }

        // PENYESUAIAN RUMUS DASHBOARD: KAS (101) JADI 70M, PIUTANG (104) JADI 150M
        $admin = Pengguna::first();
        $date = now()->endOfDay();
        
        $kasBalance = JournalEntry::where('no_ref_akun', '101')
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE -nominal END) as total")
            ->value('total') ?? 0;
            
        $piutangBalance = JournalEntry::where('no_ref_akun', '104')
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE -nominal END) as total")
            ->value('total') ?? 0;

        $groupId = \Illuminate\Support\Str::uuid();
        
        // Sesuaikan Kas ke 70M
        if ($kasBalance > 70000000) {
            $diffKas = $kasBalance - 70000000;
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '102', 'position' => 'Debit', 'nominal' => $diffKas, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '101', 'position' => 'Credit', 'nominal' => $diffKas, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
        } elseif ($kasBalance < 70000000) {
            $diffKas = 70000000 - $kasBalance;
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '101', 'position' => 'Debit', 'nominal' => $diffKas, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '102', 'position' => 'Credit', 'nominal' => $diffKas, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
        }

        // Sesuaikan Piutang ke 150M
        if ($piutangBalance > 150000000) {
            $diffPiutang = $piutangBalance - 150000000;
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '102', 'position' => 'Debit', 'nominal' => $diffPiutang, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '104', 'position' => 'Credit', 'nominal' => $diffPiutang, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
        } elseif ($piutangBalance < 150000000) {
            $diffPiutang = 150000000 - $piutangBalance;
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '104', 'position' => 'Debit', 'nominal' => $diffPiutang, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
            JournalEntry::create(['journal_group_id' => $groupId, 'no_ref_akun' => '102', 'position' => 'Credit', 'nominal' => $diffPiutang, 'tgl_transaksi' => $date, 'updated_by' => $admin->id]);
        }
    }

    private function getUniqueTransCode(): string
    {
        return 'TP' . str_pad(self::$transCodeCounter++, 8, '0', STR_PAD_LEFT);
    }

    private function seedActiveFinancing(Anggota $anggota, array $item, string $kolektibilitas = 'lancar'): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Administrator Sistem'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.2); // 20% margin
        $downPayment = (int)($item['price'] * 0.1); // 10% DP

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

        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
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
        
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'position' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '102', // Dana Alokasi Pembiayaan
            'position' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);

        if ($downPayment > 0) {
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '101', // Kas
                'position' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'position' => 'Credit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
        }

        $piutangPokok = $pembiayaan->harga_perolehan - $downPayment;
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '104', // Piutang Murabahah
            'position' => 'Debit',
            'nominal' => $piutangPokok,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);
        if ($downPayment > 0) {
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'position' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
        }
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'position' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);

        // 2. Buat Angsuran sesuai skenario kolektibilitas
        for ($i = 1; $i <= $tenor; $i++) {
            $monthlyPayment = ($pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan - $pembiayaan->uang_muka) / $tenor;
            $monthlyMargin = $pembiayaan->margin_keuntungan / $tenor;
            $monthlyCostPrice = ($pembiayaan->harga_perolehan - $pembiayaan->uang_muka) / $tenor;
            $dueDate = $akadDate->copy()->addMonths($i);

            // Tentukan status pembayaran cicilan
            $isPaid = false;
            if ($kolektibilitas === 'lancar') {
                $isPaid = $dueDate->isPast();
            } else {
                $batasTunggakan = now()->subMonths($unpaidMonthsAgo)->startOfDay();
                $isPaid = $dueDate->isBefore($batasTunggakan);
            }

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

                JournalEntry::create([
                    'journal_group_id' => $journal->id,
                    'no_ref_akun' => '101',
                    'position' => 'Debit',
                    'nominal' => $monthlyPayment,
                    'updated_by' => $admin?->id,
                    'tgl_transaksi' => $dueDate,
                ]);

                JournalEntry::create([
                    'journal_group_id' => $journal->id,
                    'no_ref_akun' => '104', // Piutang Murabahah
                    'position' => 'Credit',
                    'nominal' => $monthlyCostPrice,
                    'updated_by' => $admin?->id,
                    'tgl_transaksi' => $dueDate,
                ]);

                JournalEntry::create([
                    'journal_group_id' => $journal->id,
                    'no_ref_akun' => '401', // Pendapatan Margin
                    'position' => 'Credit',
                    'nominal' => $monthlyMargin,
                    'updated_by' => $admin?->id,
                    'tgl_transaksi' => $dueDate,
                ]);
            }
        }
    }

    private function seedPendingFinancing(Anggota $anggota, array $item): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);

        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
            'harga_perolehan' => $item['price'],
            'margin_keuntungan' => $margin,
            'uang_muka' => $downPayment,
            'tgl_permohonan' => now(),
            'status' => FinancingReqStatusEnum::PENDING_REVIEW->value,
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

        JournalEntry::create([
            'journal_group_id' => $journal->id,
            'no_ref_akun' => '103',
            'position' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => now(),
        ]);

        JournalEntry::create([
            'journal_group_id' => $journal->id,
            'no_ref_akun' => '102',
            'position' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => now(),
        ]);
    }

    private function seedCompletedFinancing(Anggota $anggota, array $item): void
    {
        $admin = Pengguna::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first() ?? Pengguna::first();
        $margin = (int)($item['price'] * 0.1);
        $downPayment = (int)($item['price'] * 0.1);
        $tenor = 10;

        $pembiayaan = Pembiayaan::create([
            'kode_pembiayaan' => 'PM' . strtoupper(uniqid()),
            'anggota_id' => $anggota->id,
            'harga_perolehan' => $item['price'],
            'margin_keuntungan' => $margin,
            'uang_muka' => $downPayment,
            'tgl_akad' => now()->subMonths($tenor),
            'tgl_lunas' => now(),
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
        
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'position' => 'Debit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '102', // Dana Alokasi Pembiayaan
            'position' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);

        if ($downPayment > 0) {
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '101', // Kas
                'position' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'position' => 'Credit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
        }

        $piutangPokok = $pembiayaan->harga_perolehan - $downPayment;
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '104', // Piutang Murabahah
            'position' => 'Debit',
            'nominal' => $piutangPokok,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);
        if ($downPayment > 0) {
            JournalEntry::create([
                'journal_group_id' => $akadJournal->id,
                'no_ref_akun' => '204', // Uang Muka Murabahah
                'position' => 'Debit',
                'nominal' => $downPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $akadDate,
            ]);
        }
        JournalEntry::create([
            'journal_group_id' => $akadJournal->id,
            'no_ref_akun' => '103', // Pembiayaan Dalam Proses
            'position' => 'Credit',
            'nominal' => $pembiayaan->harga_perolehan,
            'updated_by' => $admin?->id,
            'tgl_transaksi' => $akadDate,
        ]);

        for ($i = 1; $i <= $tenor; $i++) {
            $monthlyPayment = ($pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan - $pembiayaan->uang_muka) / $tenor;
            $monthlyMargin = $pembiayaan->margin_keuntungan / $tenor;
            $monthlyCostPrice = ($pembiayaan->harga_perolehan - $pembiayaan->uang_muka) / $tenor;

            $akadDate = Carbon::parse($pembiayaan->tgl_akad);
            $dueDate = $akadDate->copy()->addMonths($i);

            $angsuran = Angsuran::create([
                'pembiayaan_id' => $pembiayaan->id,
                'angsuran_ke' => $i,
                'tgl_jatuh_tempo' => $dueDate,
                'nominal_angsuran' => $monthlyPayment,
                'status' => $dueDate->isPast() ? InstallmentPaymentScheduleStatusEnum::PENDING->value : InstallmentPaymentScheduleStatusEnum::PAID->value,
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
            JournalEntry::create([
                'journal_group_id' => $journal->id,
                'no_ref_akun' => '101',
                'position' => 'Debit',
                'nominal' => $monthlyPayment,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $dueDate,
            ]);

            // piutang murabahah
            JournalEntry::create([
                'journal_group_id' => $journal->id,
                'no_ref_akun' => '104',
                'position' => 'Credit',
                'nominal' => $monthlyCostPrice,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $dueDate,
            ]);

            // pendapatan margin murabahah
            JournalEntry::create([
                'journal_group_id' => $journal->id,
                'no_ref_akun' => '401',
                'position' => 'Credit',
                'nominal' => $monthlyMargin,
                'updated_by' => $admin?->id,
                'tgl_transaksi' => $dueDate,
            ]);
        }
    }
}
