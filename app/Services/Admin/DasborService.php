<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Pembiayaan;
use App\Models\GlobalSetting;
use App\Models\Angsuran;
use App\Models\JournalEntry;
use App\Models\Notification;
use App\Models\SavingTransaction;
use App\Models\Pengguna;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class DasborService
{
    public function getPeriodeSebelumnya(Carbon $awal, string $filter): array
    {
        return match ($filter) {
            'month' => [
                $awal->copy()->subMonth()->startOfMonth(),
                $awal->copy()->subMonth()->endOfMonth()
            ],
            'year' => [
                $awal->copy()->subYear()->startOfYear(),
                $awal->copy()->subYear()->endOfYear()
            ],
            default => [
                $awal->copy()->subDay(),
                $awal->copy()->subDay()
            ],
        };
    }

    public function hitungPersen($sekarang, $sebelumnya): int
    {
        return $sebelumnya == 0 ? 0 : round((($sekarang - $sebelumnya) / $sebelumnya) * 100);
    }

    public function getTotalAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, $filter): array
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => Pengguna::where('status', $filter)->with('roles')->whereHas(
                'roles',
                fn($q) => $q->where('name', UserRoleEnum::ANGGOTA->value)
            )->where('created_at', '<=', $tgl)->count(),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTotalPengurus($tanggalAkhir, $tanggalAkhirSebelumnya): array
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => Pengguna::where('status', UserStatusEnum::ACTIVE->value)->with('roles')->whereHas(
                'roles',
                fn($q) => $q->where('name', '!=', UserRoleEnum::ANGGOTA->value)
            )->where('created_at', '<=', $tgl)->count(),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTotalKas($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => $this->getSaldoAkun('101', $tgl),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTransaksiTerbaru($filter, $role)
    {
        $amount = $role === UserRoleEnum::DPS->value ? 5 : 6;
        $transaksiSimpanan = SavingTransaction::with('akunSimpanan.anggota.user')
            ->latest()->take($amount)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'no_transaksi' => $t->saving_transaction_code,
                'anggota' => $t->akunSimpanan->anggota->user->nama,
                'jumlah' => $t->amount,
                'produk' => $t->akunSimpanan->jenis_simpanan,
                'akad' => $this->getAkadSimpanan($t->akunSimpanan->jenis_simpanan),
                'tanggal' => $t->created_at->toDateString(),
            ]);

        $transaksiPembiayaan = Pembiayaan::with('anggota.user', 'objekPembiayaan')
            ->latest()->take($amount)->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'no_transaksi' => $f->kode_pembiayaan,
                'anggota' => $f->anggota->user->nama,
                'jumlah' => $f->amount,
                'produk' => 'Pembiayaan',
                'akad' => 'Murabahah',
                'tanggal' => $f->created_at->toDateString(),
            ]);

        $data = $filter === 'all' ? $transaksiSimpanan->concat($transaksiPembiayaan)
            ->sortByDesc('tanggal')
            ->take($amount)
            ->values()
            ->toArray() : ($filter === 'simpanan' ? $transaksiSimpanan : $transaksiPembiayaan)->toArray();

        return $data;
    }

    public function getPendapatanPerPeriode($tanggalAwal, $tanggalAkhir, $filter)
    {
        [$data, $format] = $this->buildSkeletonPeriode($tanggalAkhir, $filter);
        [$rangeAwal, $rangeAkhir] = $this->getRangeUntukFilterPeriode($tanggalAkhir, $filter);

        $pendapatan = JournalEntry::where('no_ref_account', '401')
            ->whereBetween('transaction_date', [$rangeAwal, $rangeAkhir])
            ->get()
            ->groupBy(fn($entry) => Carbon::parse($entry->transaction_date)->format($format))
            ->map(fn($group) => $group->sum('nominal'));

        $result = $data->replace($pendapatan);

        return $result->toArray();
    }

    public function getTotalAnggotaPerPeriode($tanggalAwal, $tanggalAkhir, $filter)
    {
        [$data, $format] = $this->buildSkeletonPeriode($tanggalAkhir, $filter);

        $anggota = Pengguna::where('status', UserStatusEnum::ACTIVE->value)
            ->with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', UserRoleEnum::ANGGOTA->value))
            ->whereBetween('created_at', [$tanggalAwal, $tanggalAkhir])
            ->get()
            ->groupBy(fn($user) => $user->created_at->format($format))
            ->map(fn($group) => $group->count());

        $result = $data->replace($anggota);

        return $result->toArray();
    }

    public function getRasioKas($tanggalAkhir)
    {
        $totalKas = $this->getSaldoAkun('101', $tanggalAkhir, 'Debit', 'Credit');

        $totalLiabilitas = $this->getSaldoAkun(['201', '202', '203'], $tanggalAkhir, 'Credit', 'Debit');

        $rasioKas = 0;
        if ($totalLiabilitas > 0) {
            $rasioKas = ($totalKas / $totalLiabilitas) * 100;
        }

        return round($rasioKas, 2) . '%';
    }

    public function getRasioFDR($tanggalAkhir)
    {
        $totalPembiayaan = $this->getSaldoAkun('104', $tanggalAkhir, 'Debit', 'Credit');

        $totalDeposit = $this->getSaldoAkun(['201', '202', '203'], $tanggalAkhir, 'Credit', 'Debit');

        $rasioFDR = 0;
        if ($totalDeposit > 0) {
            $rasioFDR = ($totalPembiayaan / $totalDeposit) * 100;
        }

        return round($rasioFDR, 2) . '%';
    }

    public function getTotalSimpanan($tanggalAkhir, $tanggalAkhirSebelumnya, $tipe)
    {
        $akunSimpanan = ['201', '202', '203', '301', '302'];

        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => $this->getSaldoAkun($akunSimpanan, $tgl, posisiDebit: null, posisiCredit: $tipe),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTotalSimpananAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, $tipe)
    {
        $total = SavingTransaction::with('akunSimpanan.anggota')
            ->whereHas('akunSimpanan.anggota', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            })
            ->where('transaction_type', $tipe)
            ->where('created_at', '<=', $tanggalAkhir)
            ->sum('saving_amount');

        $persen = $this->hitungPersen(
            $total,
            SavingTransaction::whereHas('akunSimpanan.anggota', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            })
                ->where('transaction_type', $tipe)
                ->where('created_at', '<=', $tanggalAkhirSebelumnya)
                ->sum('saving_amount')
        );

        return [$total, $persen];
    }

    public function getPetaSimpanan($tanggalAkhir, $filter)
    {
        $skeletonJenis = [
            'Simpanan Pokok' => 0,
            'Simpanan Wajib' => 0,
            'Tabungan Anggota' => 0,
            'Tabungan Berjangka' => 0,
            'Tabungan Ibadah' => 0,
        ];

        $skeletonAkad = [
            'Musyarakah' => 0,
            'Wadiah Yad Dhamanah' => 0,
            'Mudharabah Mutlaqah' => 0,
        ];

        $res = collect();

        if ($filter === 'jenis') {
            $data = JournalEntry::whereIn('no_ref_account', ['201', '202', '203', '301', '302'])
                ->where('transaction_date', '<=', $tanggalAkhir)
                ->get()
                ->groupBy(fn($entry) => match ($entry->no_ref_account) {
                    '201' => 'Tabungan Anggota',
                    '202' => 'Tabungan Berjangka',
                    '203' => 'Tabungan Ibadah',
                    '301' => 'Simpanan Pokok',
                    '302' => 'Simpanan Wajib',
                    default => null,
                })
                ->map(fn($group) => $this->hitungSaldoCreditMinusDebit($group));

            $res = collect($skeletonJenis)->replace($data)->sortDesc();
        } else if ($filter === 'akad') {
            $data = JournalEntry::whereIn('no_ref_account', ['201', '202', '203', '301', '302'])
                ->where('transaction_date', '<=', $tanggalAkhir)
                ->get()
                ->groupBy(fn($entry) => match ($entry->no_ref_account) {
                    '202', '203' => 'Mudharabah Mutlaqah',
                    '201' => 'Wadiah Yad Dhamanah',
                    '301', '302' => 'Musyarakah',
                    default => null,
                })
                ->map(fn($group) => $this->hitungSaldoCreditMinusDebit($group));

            $res = collect($skeletonAkad)->replace($data)->sortDesc();
        }

        return $res->toArray();
    }

    public function getJatuhTempoTerdekat($filter)
    {
        $savingDueDate = GlobalSetting::where('key', 'due_date_simpanan')->first()->value ?? 30;
        $savingNominal = GlobalSetting::where('key', 'saving_wajib_amount')->first()->value ?? 0;

        $query = Notification::with([
            'anggota.user',
            'reference' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    SavingTransaction::class => ['akunSimpanan'],
                    Angsuran::class => ['pembiayaan'],
                ]);
            }
        ])
            ->whereHas('anggota', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            });

        if ($filter === 'simpanan') {
            $query->where('reference_type', SavingTransaction::class);
        } elseif ($filter === 'pembiayaan') {
            $query->where('reference_type', Angsuran::class);
        } else {
            $query->whereIn('reference_type', [
                SavingTransaction::class,
                Angsuran::class
            ]);
        }

        $allTransactions = $query->latest()->get()
            ->map(function ($notif) use ($savingDueDate, $savingNominal) {
                $ref = $notif->reference;

                if (!$ref) return null;

                if ($notif->reference_type === SavingTransaction::class) {
                    return $this->mapJatuhTempoSimpanan($notif, $ref, $savingDueDate, $savingNominal);
                }

                if ($notif->reference_type === Angsuran::class) {
                    return $this->mapJatuhTempoInstallment($notif, $ref);
                }

                return null;
            })
            ->filter()
            ->unique('id')
            ->sortBy('jatuh_tempo')
            ->take(7)
            ->values()
            ->toArray();

        return $allTransactions;
    }

    public function getPermohonanMurabahahTerbaru($tanggalAwal, $tanggalAkhir)
    {
        return Pembiayaan::with('anggota.user', 'objekPembiayaan')
            ->whereBetween('tgl_permohonan', [$tanggalAwal, $tanggalAkhir])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'no_transaksi' => $f->kode_pembiayaan,
                'anggota' => $f->anggota->user->nama,
                'status' => $f->status,
            ]);
    }

    public function getPembayaranTerlambat($tanggalAkhir)
    {
        $data = Angsuran::with('pembiayaan.anggota.user')
            ->where('tgl_jatuh_tempo', '<=', $tanggalAkhir)
            ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'no_transaksi' => $i->pembiayaan->kode_pembiayaan,
                'anggota' => $i->pembiayaan->anggota->user->nama,
                'jumlah' => $i->nominal_angsuran,
                'hari_terlambat' => round(Carbon::parse($i->tgl_jatuh_tempo)->diffInDays(Carbon::parse($tanggalAkhir))),
            ]);

        return $data->toArray();
    }

    public function getTransaksiSimpananTerbaru($tanggalAkhir, $filter)
    {
        $query = SavingTransaction::with(['akunSimpanan.anggota.user'])
            ->whereHas('akunSimpanan.anggota', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            })
            ->where('created_at', '<=', $tanggalAkhir);

        if ($filter !== 'all') {
            $query->whereHas('akunSimpanan', function ($q) use ($filter) {
                $q->where('jenis_simpanan', $filter);
            });
        }

        $savings = $query->latest()
            ->take(7)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'no_transaksi' => $t->saving_transaction_code,
                'anggota' => $t->akunSimpanan->anggota?->user?->nama ?? '-',
                'jumlah' => $t->saving_amount,
                'produk' => $t->akunSimpanan->jenis_simpanan,
            ])
            ->toArray();

        return $savings;
    }

    public function getTotalAngsuranBelumLunas()
    {
        $total = Angsuran::with('pembiayaan.anggota')
            ->whereHas('pembiayaan.anggota', function ($query) {
                $query->where('pj_anggota_id', auth()->id());
            })
            ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->sum('nominal_angsuran');

        return $total;
    }

    public function getJumlahPiutangMurabahahAktif($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => $this->getSaldoAkun('104', $tgl),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getPetaPembiayaan($tanggalAkhir)
    {
        $skeleton = [
            'Lancar' => 0,
            'Kurang Lancar' => 0,
            'Diragukan' => 0,
            'Macet' => 0,
        ];

        // Gunakan endOfDay() agar perhitungan mencakup transaksi hingga malam hari
        $targetDate = Carbon::parse($tanggalAkhir)->endOfDay();

        // Cari semua pembiayaan dengan status aktif dan ambil jadwal angsurannya yang belum lunas
        $pembiayaan = Pembiayaan::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->with(['angsuran' => function ($query) {
                $query->whereIn('status', [
                    InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
                ]);
            }])
            ->get();

        foreach ($pembiayaan as $item) {
            $kategori = $this->klasifikasikanKolektibilitasPembiayaan($item, $targetDate);
            $skeleton[$kategori]++;
        }

        return $skeleton;
    }

    public function getTotalModalSudahDialokasi($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => $this->getSaldoAkun('102', $tgl),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTotalPembiayaanAktif($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => Pembiayaan::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
                ->where('tgl_akad', '<=', $tgl)
                ->count(),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    public function getTotalPermohonanPembiayaan($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $statusDihitung = [
            FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            FinancingReqStatusEnum::PENDING_REVIEW->value,
            FinancingReqStatusEnum::APPROVED->value,
            FinancingReqStatusEnum::REJECTED->value,
            FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
        ];

        return $this->getTotalDenganPersenPerubahan(
            fn($tgl) => Pembiayaan::whereIn('status', $statusDihitung)
                ->where('tgl_permohonan', '<=', $tgl)
                ->count(),
            $tanggalAkhir,
            $tanggalAkhirSebelumnya
        );
    }

    // ==========================================================
    // Helper privat / lokal
    // ==========================================================

    /**
     * Membangun skeleton (kerangka) periode waktu berisi 0 untuk setiap titik waktu
     * (harian/bulanan/tahunan), agar grafik tetap menampilkan titik data yang kosong.
     *
     * @return array{0: Collection, 1: string} [$skeletonData, $dateFormat]
     */
    private function buildSkeletonPeriode($tanggalAkhir, string $filter): array
    {
        $data = collect();
        [$rangeAwal, $rangeAkhir] = $this->getRangeUntukFilterPeriode($tanggalAkhir, $filter);

        $format = match ($filter) {
            'day' => 'd M',
            'month' => 'M',
            'year' => 'Y',
            default => '',
        };

        switch ($filter) {
            case 'day':
                foreach (CarbonPeriod::create($rangeAwal, $rangeAkhir) as $date) {
                    $data->put($date->format($format), 0);
                }
                break;

            case 'month':
                $tahun = $rangeAwal->year;
                for ($m = 1; $m <= 12; $m++) {
                    $data->put(Carbon::create($tahun, $m, 1)->format($format), 0);
                }
                break;

            case 'year':
                $tahunAkhir = $rangeAkhir->year;
                for ($y = $tahunAkhir - 4; $y <= $tahunAkhir; $y++) {
                    $data->put(Carbon::create($y, 1, 1)->format($format), 0);
                }
                break;
        }

        return [$data, $format];
    }

    /**
     * Menghitung rentang tanggal (awal-akhir) aktual yang dipakai untuk query,
     * berdasarkan filter periode ('day' | 'month' | 'year').
     *
     * @return array{0: Carbon, 1: Carbon} [$rangeAwal, $rangeAkhir]
     */
    private function getRangeUntukFilterPeriode($tanggalAkhir, string $filter): array
    {
        return match ($filter) {
            // 7 hari terakhir dari $tanggalAkhir
            'day' => [
                Carbon::parse($tanggalAkhir)->subDays(6)->startOfDay(),
                Carbon::parse($tanggalAkhir)->endOfDay(),
            ],
            // 12 bulan tahun $tanggalAkhir
            'month' => [
                Carbon::create(Carbon::parse($tanggalAkhir)->year, 1, 1)->startOfDay(),
                Carbon::create(Carbon::parse($tanggalAkhir)->year, 12, 31)->endOfDay(),
            ],
            // 5 tahun terakhir dari $tanggalAkhir
            'year' => [
                Carbon::parse($tanggalAkhir)->subYears(4)->startOfYear(),
                Carbon::parse($tanggalAkhir)->endOfYear(),
            ],
            default => [
                Carbon::parse($tanggalAkhir),
                Carbon::parse($tanggalAkhir),
            ],
        };
    }

    /**
     * Pola umum: hitung total saat ini, lalu hitung persentase perubahan
     * dibanding total periode sebelumnya, menggunakan query builder yang sama.
     *
     * @param  callable(mixed $tanggal): (int|float)  $queryBuilder
     */
    private function getTotalDenganPersenPerubahan(callable $queryBuilder, $tanggalAkhir, $tanggalAkhirSebelumnya): array
    {
        $total = $queryBuilder($tanggalAkhir);
        $persen = $this->hitungPersen($total, $queryBuilder($tanggalAkhirSebelumnya));

        return [$total, $persen];
    }

    /**
     * Menghitung saldo akun dari JournalEntry hingga tanggal tertentu.
     *
     * Mode "saldo normal" (default): saldo = SUM(posisiDebit) - SUM(posisiCredit).
     * Mode "satu posisi saja": kirim null pada salah satu posisi (debit/credit)
     * untuk hanya men-sum nominal pada posisi yang tidak null tersebut.
     *
     * @param  string|array  $noRefAccount  satu kode akun atau array kode akun
     */
    private function getSaldoAkun(
        string|array $noRefAccount,
        $tanggalAkhir,
        ?string $posisiDebit = 'Debit',
        ?string $posisiCredit = 'Credit'
    ): float {
        $query = JournalEntry::where('transaction_date', '<=', $tanggalAkhir);

        is_array($noRefAccount)
            ? $query->whereIn('no_ref_account', $noRefAccount)
            : $query->where('no_ref_account', $noRefAccount);

        // Mode "satu posisi saja": kalau salah satu posisi null, sum langsung tanpa pengurangan
        if ($posisiDebit === null || $posisiCredit === null) {
            $posisi = $posisiDebit ?? $posisiCredit;

            return (float) $query->where('position', $posisi)->sum('nominal');
        }

        return (float) ($query->selectRaw(
            'SUM(CASE WHEN position = ? THEN nominal ELSE 0 END) - SUM(CASE WHEN position = ? THEN nominal ELSE 0 END) as total',
            [$posisiDebit, $posisiCredit]
        )->value('total') ?? 0);
    }

    /**
     * Helper untuk getPetaSimpanan: hitung saldo (Credit - Debit) dari satu grup JournalEntry
     * yang sudah dikumpulkan di memori (bukan query baru).
     */
    private function hitungSaldoCreditMinusDebit(Collection $group): float
    {
        $totalCredit = $group->where('position', 'Credit')->sum('nominal');
        $totalDebit = $group->where('position', 'Debit')->sum('nominal');

        return $totalCredit - $totalDebit;
    }

    /**
     * Helper untuk getJatuhTempoTerdekat: mapping notifikasi bertipe SavingTransaction.
     * Mengembalikan null jika bukan Simpanan Wajib (di luar scope notifikasi jatuh tempo simpanan).
     */
    private function mapJatuhTempoSimpanan($notif, $ref, $savingDueDate, $savingNominal): ?array
    {
        $account = $ref->akunSimpanan;

        if ($account?->jenis_simpanan !== SavingTypeEnum::SIMPANAN_WAJIB->value) {
            return null;
        }

        return [
            'id' => $account->id,
            'anggota' => $notif->anggota?->user?->nama ?? '-',
            'nominal' => $savingNominal,
            'produk' => $account->jenis_simpanan,
            'jatuh_tempo' => Carbon::parse($ref->created_at)->addDays((int) $savingDueDate)->toDateString(),
            'status_notifikasi' => $notif->status ?? 'Belum Terkirim',
        ];
    }

    /**
     * Helper untuk getJatuhTempoTerdekat: mapping notifikasi bertipe Angsuran.
     * Mengembalikan null jika status angsuran sudah tidak terjadwal (SCHEDULED).
     */
    private function mapJatuhTempoInstallment($notif, $ref): ?array
    {
        if ($ref->status !== InstallmentPaymentScheduleStatusEnum::SCHEDULED->value) {
            return null;
        }

        return [
            'id' => $ref->id,
            'anggota' => $notif->anggota?->user?->nama ?? '-',
            'nominal' => $ref->nominal_angsuran,
            'produk' => 'Pembiayaan',
            'jatuh_tempo' => Carbon::parse($ref->tgl_jatuh_tempo)->toDateString(),
            'status_notifikasi' => $notif->status ?? 'Belum Terkirim',
        ];
    }

    /**
     * Helper untuk getPetaPembiayaan: menentukan kategori kolektibilitas
     * (Lancar / Kurang Lancar / Diragukan / Macet) untuk satu pembiayaan,
     * berdasarkan jadwal angsuran tertua yang belum lunas dan tanggal jatuh tempo kontrak.
     */
    private function klasifikasikanKolektibilitasPembiayaan(Pembiayaan $pembiayaan, Carbon $targetDate): string
    {
        $oldestUnpaid = $pembiayaan->angsuran->sortBy('tgl_jatuh_tempo')->first();

        // Jika tidak ada jadwal angsuran yang belum lunas, berarti pembiayaan ini 100% lancar
        if (!$oldestUnpaid) {
            return 'Lancar';
        }

        $dueDate = Carbon::parse($oldestUnpaid->tgl_jatuh_tempo)->startOfDay();

        // Cek 0: Jika target tanggal yang dipilih user SEBELUM due date (belum waktunya bayar)
        if ($targetDate->lessThanOrEqualTo($dueDate)) {
            return 'Lancar';
        }

        $jatuhTempoPembiayaan = Carbon::parse($pembiayaan->tgl_akad)->addMonths($pembiayaan->tenor)->endOfDay();

        // KONDISI 2: Kontrak Akad Sudah Tamat / Jatuh Tempo Pembiayaan Terlewati
        if ($targetDate->greaterThan($jatuhTempoPembiayaan)) {
            $monthsPastMaturity = $jatuhTempoPembiayaan->diffInMonths($targetDate);

            return match (true) {
                $monthsPastMaturity <= 1 => 'Kurang Lancar', // jatuh tempo sampai 1 bulan
                $monthsPastMaturity <= 2 => 'Diragukan',     // melewati 1-2 bulan
                default => 'Macet',                           // melewati 2 bulan
            };
        }

        // KONDISI 1: Kontrak Akad Masih Berjalan — hitung dari angsuran TERTUA yang belum dibayar
        $monthsLate = $dueDate->diffInMonths($targetDate);

        return match (true) {
            $monthsLate <= 3 => 'Lancar',          // tunggakan sampai 3 bulan
            $monthsLate <= 6 => 'Kurang Lancar',   // melewati 3-6 bulan
            $monthsLate <= 12 => 'Diragukan',      // melewati 6-12 bulan
            default => 'Macet',                     // melewati 12 bulan
        };
    }

    private function getAkadSimpanan($savingType): ?string
    {
        return match ($savingType) {
            SavingTypeEnum::SIMPANAN_POKOK->value, SavingTypeEnum::SIMPANAN_WAJIB->value => 'Musyarakah',
            SavingTypeEnum::TABUNGAN_ANGGOTA->value => 'Wadiah Yad Dhamanah',
            SavingTypeEnum::TABUNGAN_BERJANGKA->value, SavingTypeEnum::TABUNGAN_IBADAH->value => 'Mudharabah Mutlaqah',
            default => null,
        };
    }
}
