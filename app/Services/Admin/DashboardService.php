<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\GlobalSetting;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardService
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
        $total = User::where('status', $filter)->with('roles')->whereHas(
            'roles',
            fn($q) =>
            $q->where('name', UserRoleEnum::ANGGOTA->value)
        )->where('created_at', '<=', $tanggalAkhir)->count();

        $persen = $this->hitungPersen(
            $total,
            User::where('status', $filter)->where('created_at', '<=', $tanggalAkhirSebelumnya)->count()
        );

        return [$total, $persen];
    }

    public function getTotalPengurus($tanggalAkhir, $tanggalAkhirSebelumnya): array
    {
        $total = User::where('status', UserStatusEnum::ACTIVE->value)->with('roles')->whereHas(
            'roles',
            fn($q) =>
            $q->where('name', '!=', UserRoleEnum::ANGGOTA->value)
        )->where('created_at', '<=', $tanggalAkhir)->count();

        $persen = $this->hitungPersen(
            $total,
            User::where('status', UserStatusEnum::ACTIVE->value)->where('created_at', '<=', $tanggalAkhirSebelumnya)->count()
        );

        return [$total, $persen];
    }

    public function getTotalKas($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $kas = JournalEntry::where('no_ref_account', '101')
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $kasSebelumnya = JournalEntry::where('no_ref_account', '101')
            ->where('transaction_date', '<=', $tanggalAkhirSebelumnya)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $persen = $this->hitungPersen($kas, $kasSebelumnya);

        return [$kas, $persen];
    }

    public function getTransaksiTerbaru($filter)
    {
        $transaksiSimpanan = SavingTransaction::with('savingAccount.member.user')
            ->latest()->take(6)->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'no_transaksi' => $t->saving_transaction_code,
                'anggota' => $t->savingAccount->member->user->name,
                'jumlah' => $t->amount,
                'produk' => $t->savingAccount->saving_type,
                'akad' => $this->getAkadSimpanan($t->savingAccount->saving_type),
                'tanggal' => $t->created_at->toDateString(),
            ]);

        $transaksiPembiayaan = Financing::with('member.user', 'financingItem')
            ->latest()->take(6)->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'no_transaksi' => $f->financing_transaction_code,
                'anggota' => $f->member->user->name,
                'jumlah' => $f->amount,
                'produk' => 'Pembiayaan',
                'akad' => 'Murabahah',
                'tanggal' => $f->created_at->toDateString(),
            ]);

        $data = $filter === 'all' ? $transaksiSimpanan->concat($transaksiPembiayaan)
            ->sortByDesc('tanggal')
            ->take(6)
            ->values()
            ->toArray() : ($filter === 'simpanan' ? $transaksiSimpanan : $transaksiPembiayaan)->toArray();

        return $data;
    }

    public function getPendapatanPerPeriode($tanggalAwal, $tanggalAkhir, $filter)
    {
        $data = collect();
        $format = '';

        if ($filter === 'day') {
            // 30 hari terakhir dari $tanggalAkhir
            $tanggalAwal = Carbon::parse($tanggalAkhir)->subDays(6)->startOfDay();
            $tanggalAkhir = Carbon::parse($tanggalAkhir)->endOfDay();
            $period = CarbonPeriod::create($tanggalAwal, $tanggalAkhir);
            foreach ($period as $date) {
                $data->put($date->format('d M'), 0);
            }
            $format = 'd M';
        } else if ($filter === 'month') {
            // 12 bulan tahun $tanggalAkhir
            $tahun = Carbon::parse($tanggalAkhir)->year;
            $tanggalAwal = Carbon::create($tahun, 1, 1)->startOfDay();
            $tanggalAkhir = Carbon::create($tahun, 12, 31)->endOfDay();
            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::create($tahun, $m, 1);
                $data->put($date->format('M'), 0);
            }
            $format = 'M';
        } else if ($filter === 'year') {
            // 5 tahun terakhir dari $tanggalAkhir
            $tanggalAwal = Carbon::parse($tanggalAkhir)->subYears(4)->startOfYear();
            $tanggalAkhir = Carbon::parse($tanggalAkhir)->endOfYear();
            $tahunAkhir = $tanggalAkhir->year;
            for ($y = $tahunAkhir - 4; $y <= $tahunAkhir; $y++) {
                $date = Carbon::create($y, 1, 1);
                $data->put($date->format('Y'), 0);
            }
            $format = 'Y';
        }

        $pendapatan = JournalEntry::where('no_ref_account', '401')
            ->whereBetween('transaction_date', [$tanggalAwal, $tanggalAkhir])
            ->get()
            ->groupBy(fn($entry) => $entry->transaction_date->format($format))
            ->map(fn($group) => $group->sum('nominal'));

        $result = $data->replace($pendapatan);

        return $result->toArray();
    }

    public function getTotalAnggotaPerPeriode($tanggalAwal, $tanggalAkhir, $filter)
    {
        $start = Carbon::parse($tanggalAwal);
        $end = Carbon::parse($tanggalAkhir);

        $skeleton = collect();
        $format = '';

        if ($filter === 'month') {
            $year = $end->year;
            for ($m = 1; $m <= 12; $m++) {
                $date = Carbon::create($year, $m, 1);
                $skeleton->put($date->format('M'), 0);
            }
            $format = 'M';
        } else if ($filter === 'year') {
            $endYear = $end->year;
            for ($y = $endYear - 4; $y <= $endYear; $y++) {
                $date = Carbon::create($y, 1, 1);
                $skeleton->put($date->format('Y'), 0);
            }
            $format = 'Y';
        } else {
            $format = 'd M';
            $period = CarbonPeriod::create($start, '1 day', $end);

            foreach ($period as $date) {
                $skeleton->put($date->format($format), 0);
            }
        }
        $queryStart = $filter === 'month' || $filter === 'year' ? $end->copy()->startOfYear() : $start->startOfDay();
        $queryEnd = $filter === 'month' || $filter === 'year' ? $end->copy()->endOfYear() : $end->endOfDay();

        $queryData = User::where('status', UserStatusEnum::ACTIVE->value)
            ->with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', UserRoleEnum::ANGGOTA->value))
            ->whereBetween('created_at', [$queryStart, $queryEnd])
            ->get()
            ->groupBy(fn($user) => $user->created_at->format($format))
            ->map(fn($group) => $group->count());

        $data = $skeleton->merge($queryData);

        return $data->toArray();
    }

    public function getRasioKas($tanggalAkhir) {
        $totalKas = JournalEntry::where('no_ref_account', '101')
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->sum('nominal');

        $totalLiabilitas = JournalEntry::whereIn('no_ref_account', ['201', '202', '203'])
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->sum('nominal');

        $rasioKas = 0;
        if ($totalLiabilitas > 0) {
            $rasioKas = ($totalKas / $totalLiabilitas) * 100;
        }

        return round($rasioKas, 2) . '%';
    }

    public function getRasioFDR($tanggalAkhir) {
        $totalPembiayaan = JournalEntry::where('no_ref_account', '103')
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->sum('nominal');

        $totalDeposit = JournalEntry::whereIn('no_ref_account', ['201', '202', '203'])
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->sum('nominal');

        $rasioFDR = 0;
        if ($totalDeposit > 0) {
            $rasioFDR = ($totalPembiayaan / $totalDeposit) * 100;
        }

        return round($rasioFDR, 2) . '%';
    }

    public function getTotalSimpanan($tanggalAkhir, $tanggalAkhirSebelumnya, $tipe)
    {
        $total = JournalEntry::whereIn('no_ref_account', ['201', '202', '203', '301', '302'])
            ->where('position', $tipe)
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->sum('nominal');

        $persen = $this->hitungPersen(
            $total,
            JournalEntry::whereIn('no_ref_account', ['201', '202', '203', '301', '302'])
                ->where('position', $tipe)
                ->where('transaction_date', '<=', $tanggalAkhirSebelumnya)
                ->sum('nominal')
        );

        return [$total, $persen];
    }

    public function getTotalSimpananAnggota($tanggalAkhir, $tanggalAkhirSebelumnya, $tipe)
    {
        $total = SavingTransaction::with('savingAccount.member')
            ->whereHas('savingAccount.member', function($q) {
                $q->where('pj_user_id', auth()->id());
            })
            ->where('transaction_type', $tipe)
            ->where('created_at', '<=', $tanggalAkhir)
            ->sum('saving_amount');


        $persen = $this->hitungPersen(
            $total,
            SavingTransaction::whereHas('savingAccount.member', function($q) {
                    $q->where('pj_user_id', auth()->id());
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
            ->map(function ($group) {
                $totalCredit = $group->where('position', 'Credit')->sum('nominal');
                $totalDebit = $group->where('position', 'Debit')->sum('nominal');

                return $totalCredit - $totalDebit;
            });

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
            ->map(function ($group) {
                $totalCredit = $group->where('position', 'Credit')->sum('nominal');
                $totalDebit = $group->where('position', 'Debit')->sum('nominal');

                return $totalCredit - $totalDebit;
            });

            $res = collect($skeletonAkad)->replace($data)->sortDesc();
        }

        return $res->toArray();
    }

    public function getJatuhTempoTerdekat($filter)
    {
        $savingDueDate = GlobalSetting::where('key', 'due_date_simpanan')->first()->value ?? 30;
        $savingNominal = GlobalSetting::where('key', 'nominal_simpanan')->first()->value ?? 0;

        $transaksiSimpanan = collect();
        $transaksiPembiayaan = collect();

        if ($filter === 'all' || $filter === 'simpanan') {
            $transaksiSimpanan = SavingAccount::with([
                    'member.user',
                    'transactions' => fn($q) => $q->latest()
                ])
                ->whereHas('member', function($q) {
                    $q->where('pj_user_id', auth()->id());
                })
                ->where('saving_type', SavingTypeEnum::SIMPANAN_WAJIB->value)
                ->get()
                ->map(function($t) use ($savingDueDate, $savingNominal) {
                    $lastTransaction = $t->transactions->first();
                    $baseDate = $lastTransaction ? $lastTransaction->created_at : $t->created_at;

                    return [
                        'id' => $t->id,
                        'anggota' => $t->member?->user?->name ?? '-',
                        'nominal' => $savingNominal,
                        'produk' => $t->saving_type,
                        'jatuh_tempo' => Carbon::parse($baseDate)->addDays((int) $savingDueDate)->toDateString()
                    ];
                });
        }

        if ($filter === 'all' || $filter === 'pembiayaan') {
            $transaksiPembiayaan = Installment::with(['financing.member.user'])
                ->whereHas('financing.member', function($query) {
                    $query->where('pj_user_id', auth()->id());
                })
                ->whereIn('status', [
                    InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
                ])
                ->orderBy('due_date', 'asc')
                ->take(7)
                ->get()
                ->map(fn($f) => [
                    'id' => $f->id,
                    'anggota' => $f->financing->member?->user?->name ?? '-',
                    'nominal' => $f->amount,
                    'produk' => 'Pembiayaan',
                    'jatuh_tempo' => Carbon::parse($f->due_date)->toDateString(),
                ]);
        }

        $allTransactions = $transaksiSimpanan->concat($transaksiPembiayaan)
            ->sortBy('jatuh_tempo')
            ->take(7)
            ->values()
            ->toArray();

        return $allTransactions;
    }

    public function getPermohonanMurabahahTerbaru($tanggalAwal, $tanggalAkhir)
    {
        return Financing::with('member.user', 'financingItem')
            ->whereBetween('requested_date', [$tanggalAwal, $tanggalAkhir])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'no_transaksi' => $f->financing_transaction_code,
                'anggota' => $f->member->user->name,
                'status' => $f->status,
            ]);
    }

    public function getPembayaranTerlambat($tanggalAkhir)
    {
        $data =  Installment::with('financing.member.user')
            ->where('due_date', '<=', $tanggalAkhir)
            ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'no_transaksi' => $i->financing->financing_transaction_code,
                'anggota' => $i->financing->member->user->name,
                'jumlah' => $i->amount,
                'hari_terlambat' => Carbon::parse($i->due_date)->diffInDays(Carbon::parse($tanggalAkhir)),
            ]
        );

        return $data->toArray();
    }

    public function getTransaksiSimpananTerbaru($tanggalAkhir, $filter)
    {
        $query = SavingTransaction::with(['savingAccount.member.user'])
            ->whereHas('savingAccount.member', function ($q) {
                $q->where('pj_user_id', auth()->id());
            })
            ->where('created_at', '<=', $tanggalAkhir);

        if ($filter !== 'all') {
            $query->whereHas('savingAccount', function ($q) use ($filter) {
                $q->where('saving_type', $filter);
            });
        }

        $savings = $query->latest()
            ->take(7)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'no_transaksi' => $t->saving_transaction_code,
                'anggota' => $t->savingAccount->member?->user?->name ?? '-',
                'jumlah' => $t->saving_amount,
                'produk' => $t->savingAccount->saving_type,
            ])
            ->toArray();

        return $savings;
    }

    public function getTotalAngsuranBelumLunas()
    {
        $total = Installment::with('financing.member')
            ->whereHas('financing.member', function($query) {
                $query->where('pj_user_id', auth()->id());
            })
            ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
            ->sum('amount');

        return $total;
    }

    public function getTotalPembiayaanTersalurkan($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $modal = JournalEntry::where('no_ref_account', '104')
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $modalSebelumnya = JournalEntry::where('no_ref_account', '104')
            ->where('transaction_date', '<=', $tanggalAkhirSebelumnya)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $persen = $this->hitungPersen($modal, $modalSebelumnya);

        return [$modal, $persen];
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
        $financings = Financing::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->with(['installment' => function($query) {
                $query->whereIn('status', [
                    InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
                ]);
            }])
            ->get();

        foreach ($financings as $financing) {
            $oldestUnpaid = $financing->installment->sortBy('due_date')->first();
            $jatuhTempoPembiayaan = Carbon::parse($financing->akad_date)->addMonths($financing->tenor)->endOfDay();

            // Jika tidak ada jadwal angsuran yang belum lunas, berarti pembiayaan ini 100% lancar
            if (!$oldestUnpaid) {
                $skeleton['Lancar']++;
                continue;
            }

            $dueDate = Carbon::parse($oldestUnpaid->due_date)->startOfDay();

            // Cek 0: Jika target tanggal yang dipilih user SEBELUM due date (belum waktunya bayar)
            if ($targetDate->lessThanOrEqualTo($dueDate)) {
                $skeleton['Lancar']++;
                continue;
            }

            // KONDISI 2: Kontrak Akad Sudah Tamat / Jatuh Tempo Pembiayaan Terlewati
            if ($targetDate->greaterThan($jatuhTempoPembiayaan)) {
                // Hitung selisih bulan dari tanggal TAMAT kontraknya
                $monthsPastMaturity = $jatuhTempoPembiayaan->diffInMonths($targetDate);

                if ($monthsPastMaturity <= 1) {
                    // Pembiayaan telah jatuh tempo sampai dengan 1 bulan
                    $skeleton['Kurang Lancar']++;
                } elseif ($monthsPastMaturity <= 2) {
                    // Pembiayaan jatuh tempo telah melewati 1 bulan sampai dengan 2 bulan
                    $skeleton['Diragukan']++;
                } else {
                    // Pembiayaan jatuh tempo telah melewati 2 bulan
                    $skeleton['Macet']++;
                }
            }
            // KONDISI 1: Kontrak Akad Masih Berjalan
            else {
                // Hitung selisih bulan dari angsuran TERTUA yang belum dibayar
                $monthsLate = $dueDate->diffInMonths($targetDate);

                if ($monthsLate <= 3) {
                    // Tunggakan angsuran sampai dengan 3 bulan
                    $skeleton['Lancar']++;
                } elseif ($monthsLate <= 6) {
                    // Tunggakan angsuran yang telah melewati 3 bulan sampai dengan 6 bulan
                    $skeleton['Kurang Lancar']++;
                } elseif ($monthsLate <= 12) {
                    // Tunggakan angsuran yang telah melewati 6 bulan sampai dengan 12 bulan
                    $skeleton['Diragukan']++;
                } else {
                    // Tunggakan angsuran yang telah melewati 12 bulan
                    $skeleton['Macet']++;
                }
            }
        }

        return $skeleton;
    }

    public function getTotalModalSudahDialokasi($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $modal = JournalEntry::where('no_ref_account', '102')
            ->where('transaction_date', '<=', $tanggalAkhir)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $modalSebelumnya = JournalEntry::where('no_ref_account', '102')
            ->where('transaction_date', '<=', $tanggalAkhirSebelumnya)
            ->selectRaw("SUM(CASE WHEN position = 'Debit' THEN nominal ELSE 0 END) - SUM(CASE WHEN position = 'Credit' THEN nominal ELSE 0 END) as total")
            ->value('total') ?? 0;

        $persen = $this->hitungPersen($modal, $modalSebelumnya);

        return [$modal, $persen];
    }

    public function getTotalPembiayaanAktif($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $total = Financing::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->where('akad_date', '<=', $tanggalAkhir)
            ->count();

        $persen = $this->hitungPersen(
            $total,
            Financing::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
                ->where('akad_date', '<=', $tanggalAkhirSebelumnya)
                ->count()
        );

        return [$total, $persen];
    }

    public function getTotalPermohonanPembiayaan($tanggalAkhir, $tanggalAkhirSebelumnya)
    {
        $total = Financing::whereIn('status', [FinancingReqStatusEnum::PENDING_REVIEW->value, FinancingReqStatusEnum::APPROVED->value, FinancingReqStatusEnum::REJECTED->value])->where('requested_date', '<=', $tanggalAkhir)->count();

        $persen = $this->hitungPersen(
            $total,
            Financing::whereIn('status', [FinancingReqStatusEnum::PENDING_REVIEW->value, FinancingReqStatusEnum::APPROVED->value, FinancingReqStatusEnum::REJECTED->value])->where('requested_date', '<=', $tanggalAkhirSebelumnya)->count()
        );

        return [$total, $persen];
    }

    // lokal helper
    private function getAkadSimpanan($savingType): string
    {
        switch ($savingType) {
            case SavingTypeEnum::SIMPANAN_POKOK->value:
                return 'Musyarakah';
            case SavingTypeEnum::SIMPANAN_WAJIB->value:
                return 'Musyarakah';
            case SavingTypeEnum::TABUNGAN_ANGGOTA->value:
                return 'Wadiah Yad Dhamanah';
            case SavingTypeEnum::TABUNGAN_BERJANGKA->value:
                return 'Mudharabah Mutlaqah';
            case SavingTypeEnum::TABUNGAN_IBADAH->value:
                return 'Mudharabah Mutlaqah';
            default:
                return null;
        }
    }
}
