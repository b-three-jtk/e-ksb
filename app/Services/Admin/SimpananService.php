<?php

namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\PengaturanUmum;
use App\Models\AkunBerjangka;
use App\Models\AkunIbadah;
use App\Models\Anggota;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Models\RekeningAnggota;
use App\Services\PengaturanUmumService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SimpananService
{
    public function __construct(
        private PengaturanUmumService $pengaturanUmumService
    ) {}

    // Helpers

    public function getSettingValue(string $key): float
    {
        return (float) PengaturanUmum::where('key', $key)
            ->where('tgl_diberlakukan', '<=', now())
            ->orderByDesc('tgl_diberlakukan')
            ->value('value') ?? 0;
    }

    public function getTrxPrefix(string $category): string
    {
        return match ($category) {
            'Tabungan Anggota'   => 'TA',
            'Simpanan Pokok'     => 'SP',
            'Simpanan Wajib'     => 'SW',
            'Tabungan Berjangka' => 'TB',
            'Tabungan Ibadah'    => 'TI',
            default              => 'ST',
        };
    }

    public function getAccountPrefix(string $category): string
    {
        return match ($category) {
            'Tabungan Anggota'   => 'TA',
            'Simpanan Pokok'     => 'SP',
            'Simpanan Wajib'     => 'SW',
            'Tabungan Berjangka' => 'TB',
            'Tabungan Ibadah'    => 'TI',
            default              => 'ST',
        };
    }

    public function getTrxCodePrefix(string $category): string
    {
        return match ($category) {
            'Tabungan Anggota'   => 'TTA',
            'Simpanan Pokok'     => 'TSP',
            'Simpanan Wajib'     => 'TSW',
            'Tabungan Berjangka' => 'TTB',
            'Tabungan Ibadah'    => 'TTI',
            default              => 'TST',
        };
    }

    public function generateAccountCode(string $category): string
    {
        $prefix  = $this->getAccountPrefix($category);
        $yymm    = now()->format('ym');
        $lastNo  = AkunSimpanan::where('kode_akun_simpanan', 'like', "{$prefix}{$yymm}%")
            ->count();
        $seq     = str_pad((string)($lastNo + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$yymm}{$seq}";
    }

    public function generateTransactionCode(string $category): string
    {
        $prefix  = $this->getTrxCodePrefix($category);
        $yymm    = now()->format('ym'); // e.g. 2506
        $lastNo  = TransaksiSimpanan::where('kode_transaksi_simpanan', 'like', "{$prefix}{$yymm}%")
            ->count();
        $seq     = str_pad((string)($lastNo + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$yymm}{$seq}";
    }

    public function getExportTitle(string $tab): string
    {
        return match ($tab) {
            'simpanan'           => 'Data Semua Simpanan',
            'pokok'              => 'Data Simpanan Pokok',
            'wajib'              => 'Data Simpanan Wajib',
            'tabungan'           => 'Data Semua Tabungan',
            'tabungan_anggota'   => 'Data Tabungan Anggota',
            'tabungan_berjangka' => 'Data Tabungan Berjangka',
            'tabungan_ibadah'    => 'Data Tabungan Ibadah',
            default              => 'Data Simpanan & Tabungan',
        };
    }

    // List / Index

    public function buildBaseQuery(Request $request)
    {
        $search = $request->input('search');
        $tab    = $request->input('tab', 'semua');

        $typeMap = [
            'pokok'              => SavingTypeEnum::SIMPANAN_POKOK->value,
            'wajib'              => SavingTypeEnum::SIMPANAN_WAJIB->value,
            'tabungan_anggota'   => SavingTypeEnum::TABUNGAN_ANGGOTA->value,
            'tabungan_berjangka' => SavingTypeEnum::TABUNGAN_BERJANGKA->value,
            'tabungan_ibadah'    => SavingTypeEnum::TABUNGAN_IBADAH->value,
        ];

        $query = TransaksiSimpanan::with([
            'akunSimpanan.anggota.user',
            'akunSimpanan',
        ]);

        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('akunSimpanan.anggota', function ($q) {
                $q->where('pj_anggota_id', Auth::id());
            });
        }

        return $query
            ->when($search, function ($q) use ($search) {
                $q->whereHas('akunSimpanan.anggota.user', function ($m) use ($search) {
                    $m->where('nama', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('kode_pengguna', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('akunSimpanan', function ($sa) use ($typeMap, $tab) {
                    $sa->where('jenis_simpanan', $typeMap[$tab]);
                });
            })
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('akunSimpanan', function ($sa) {
                    $sa->whereIn('jenis_simpanan', [
                        SavingTypeEnum::SIMPANAN_POKOK->value,
                        SavingTypeEnum::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('akunSimpanan', function ($sa) {
                    $sa->whereIn('jenis_simpanan', [
                        SavingTypeEnum::TABUNGAN_ANGGOTA->value,
                        SavingTypeEnum::TABUNGAN_BERJANGKA->value,
                        SavingTypeEnum::TABUNGAN_IBADAH->value,
                    ]);
                });
            });
    }

    public function getTransactionList(Request $request): array
    {
        $perPage  = $request->input('per_page', 10);
        $tab      = $request->input('tab', 'semua');
        $sortBy   = in_array($request->input('sort_by'), ['tgl_transaksi']) ? $request->input('sort_by') : 'tgl_transaksi';
        $sortDir  = $request->input('sort_dir', 'desc');

        $transactions = $this->buildBaseQuery($request)
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($trx) => [
                'id'           => $trx->id,
                'no_transaksi' => $trx->kode_transaksi_simpanan,
                'tanggal'      => Carbon::parse($trx->tgl_transaksi)->format('d/m/Y'),
                'anggota'      => $trx->akunSimpanan->anggota->user->kode_pengguna
                                . ' - '
                                . $trx->akunSimpanan->anggota->user->nama,
                'nominal'      => $trx->tipe_transaksi === TransactionTypeEnum::WITHDRAWAL->value
                                ? -$trx->nominal_simpanan
                                : $trx->nominal_simpanan,
                'produk'       => $trx->akunSimpanan->jenis_simpanan,
                'jenis'        => $trx->tipe_transaksi,
            ]);

        $summaryBase     = $this->buildBaseQuery($request);
        $totalMasuk      = (clone $summaryBase)->where('tipe_transaksi', 'Penyetoran')->sum('nominal_simpanan');
        $totalKeluar     = (clone $summaryBase)->where('tipe_transaksi', 'Penarikan')->sum('nominal_simpanan');
        $totalPerputaran = $totalMasuk + $totalKeluar;

        $tabLabels = [
            'semua'              => 'Simpanan & Tabungan',
            'simpanan'           => 'Semua Simpanan',
            'pokok'              => 'Simpanan Pokok',
            'wajib'              => 'Simpanan Wajib',
            'tabungan'           => 'Semua Tabungan',
            'tabungan_anggota'   => 'Tabungan Anggota',
            'tabungan_berjangka' => 'Tabungan Berjangka',
            'tabungan_ibadah'    => 'Tabungan Ibadah',
        ];
        $label = $tabLabels[$tab] ?? 'Simpanan & Tabungan';

        $summary = [
            [
                'title'      => "Total {$label}",
                'value'      => 'Rp ' . number_format($totalMasuk - $totalKeluar, 0, ',', '.'),
                'percentage' => $totalMasuk > 0
                    ? round((($totalMasuk - $totalKeluar) / $totalMasuk) * 100)
                    : 0,
            ],
            [
                'title'      => "Total {$label} Masuk",
                'value'      => 'Rp ' . number_format($totalMasuk, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalMasuk / $totalPerputaran) * 100)
                    : 0,
            ],
            [
                'title'      => "Total {$label} Keluar",
                'value'      => 'Rp ' . number_format($totalKeluar, 0, ',', '.'),
                'percentage' => $totalPerputaran > 0
                    ? round(($totalKeluar / $totalPerputaran) * 100)
                    : 0,
            ],
        ];

        return [
            'transactions' => $transactions,
            'summary'      => $summary,
            'filters'      => [
                'search'   => $request->input('search'),
                'per_page' => $perPage,
                'tab'      => $tab,
                'sort_by'  => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ];
    }

    // Members for deposit form 

    public function getMembersForDeposit(): \Illuminate\Support\Collection
    {
        $query = Anggota::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
            MemberStatusEnum::PAYMENT_PENDING->value,
        ])
        ->when(
            Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value),
            fn($q) => $q->where('pj_anggota_id', Auth::id())
        )
        ->with([
            'user:id,kode_pengguna,nama',
            'akunSimpanan.ibadah',
            'akunSimpanan.berjangka',
            'bankAccounts',
        ]);

        return $query->get()->map(fn($anggota) => [
            'id'            => $anggota->id,
            'kode_pengguna'     => $anggota->user->kode_pengguna,
            'nama'          => $anggota->user->nama,
            'status'        => $anggota->status,
            'akunSimpanan' => $anggota->akunSimpanan
                ->filter(function ($acc) {
                    if ($acc->jenis_simpanan === 'Tabungan Ibadah')    return $acc->ibadah;
                    if ($acc->jenis_simpanan === 'Tabungan Berjangka') return $acc->berjangka;
                    return true;
                })
                ->map(fn($acc) => [
                    'id'            => $acc->id,
                    'type'          => $acc->jenis_simpanan ?? null,
                    'tujuan'       => $acc->berjangka?->tujuan ?? $acc->ibadah?->tujuan,
                    'saldo'       => $acc->saldo ?? 0,
                    'target_tabungan' => $acc->ibadah?->target_tabungan,
                    'matured_at'    => $acc->berjangka
                        ? $acc->created_at->copy()->addMonths($acc->berjangka->tenor)->format('d M Y')
                        : null,
                    'is_frozen'     => $acc->ibadah
                        ? $acc->saldo >= $acc->ibadah->target_tabungan
                        : false,
                    'is_matured'    => $acc->berjangka
                        ? now()->gte($acc->created_at->copy()->addMonths($acc->berjangka->tenor))
                        : false,
                ]),
            'rekeningAnggota' => $anggota->bankAccounts
                ->map(fn ($rekening) => [
                    'no_rekening' => $rekening->no_rekening,
                    'nama_bank'   => $rekening->nama_bank,
                    'atas_nama'   => $rekening->atas_nama,
                ])
                ->values()
                ->toArray(),
        ]);
    }

    // Store Deposit

    public function resolveOrCreateSavingAccount(array $data, Anggota $anggota): AkunSimpanan
    {
        if (filled($data['akun_simpanan_id'] ?? null)) {
            return AkunSimpanan::where('id', $data['akun_simpanan_id'])
                ->where('anggota_id', $anggota->id)
                ->firstOrFail();
        }

        if (in_array($data['saving_category'], [
            SavingTypeEnum::SIMPANAN_POKOK->value,
            SavingTypeEnum::SIMPANAN_WAJIB->value,
            SavingTypeEnum::TABUNGAN_ANGGOTA->value,
        ])) {
            return AkunSimpanan::firstOrCreate(
                ['anggota_id' => $anggota->id, 'jenis_simpanan' => $data['saving_category']],
                ['kode_akun_simpanan' => $this->generateAccountCode($data['saving_category'])]
            );
        }

        return AkunSimpanan::create([
            'anggota_id'           => $anggota->id,
            'jenis_simpanan'         => $data['saving_category'],
            'kode_akun_simpanan' => $this->generateAccountCode($data['saving_category']),
        ]);
    }

    public function resolveOrCreateMemberBankAccount(array $data, Anggota $anggota): ?RekeningAnggota
    {
        if (($data['metode_pembayaran_simpanan'] ?? null) !== 'Non-Tunai') {
            return null;
        }

        if (
            empty($data['no_rekening']) ||
            empty($data['nama_bank']) ||
            empty($data['atas_nama'])
        ) {
            return null;
        }

        return RekeningAnggota::firstOrCreate(
            [
                'no_rekening' => $data['no_rekening'],
            ],
            [
                'anggota_id' => $anggota->id,
                'nama_bank'  => $data['nama_bank'],
                'atas_nama'  => $data['atas_nama'],
            ]
        );
    }

    public function validateDepositRules(array $data, AkunSimpanan $akunSimpanan, Anggota $anggota): void
    {
        $isNewAccount = empty($data['akun_simpanan_id']);
        if ($isNewAccount && $data['saving_category'] === 'Tabungan Berjangka') {
            if (empty($data['tenor_months'])) {
                throw ValidationException::withMessages([
                    'tenor_months' => 'Jatuh tempo wajib diisi untuk tabungan berjangka baru.',
                ]);
            }

            AkunBerjangka::create([
                'akun_simpanan_id' => $akunSimpanan->id,
                'tujuan'           => $data['tujuan'] ?? null,
                'tenor'             => $data['tenor_months'],
            ]);
        }

        if ($data['saving_category'] === SavingTypeEnum::SIMPANAN_POKOK->value) {
            $expected = $this->getSettingValue('saving_pokok_amount');

            if ((float)$data['amount'] != $expected) {
                throw ValidationException::withMessages([
                    'amount' => 'Simpanan Pokok harus sebesar Rp ' . number_format($expected, 0, ',', '.'),
                ]);
            }

            if (TransaksiSimpanan::where('akun_simpanan_id', $akunSimpanan->id)
                ->where('tipe_transaksi', TransactionTypeEnum::DEPOSIT->value)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.',
                ]);
            }

            if ($anggota->status !== MemberStatusEnum::PAYMENT_PENDING->value) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya untuk anggota Menunggu Pembayaran.',
                ]);
            }
        }

        if ($data['saving_category'] === SavingTypeEnum::SIMPANAN_WAJIB->value) {
            $expected = $this->getSettingValue('saving_wajib_amount');

            if (abs((float)$data['amount'] - (float)$expected) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Simpanan Wajib harus sebesar Rp ' . number_format($expected, 0, ',', '.'),
                ]);
            }
        }

        if ($data['saving_category'] === SavingTypeEnum::TABUNGAN_IBADAH->value) {
            if ($akunSimpanan->wasRecentlyCreated) {
                if (empty($data['target_tabungan'])) {
                    throw ValidationException::withMessages([
                        'target_tabungan' => 'Target tabungan wajib diisi.',
                    ]);
                }

                AkunIbadah::create([
                    'akun_simpanan_id' => $akunSimpanan->id,
                    'tujuan'           => $data['tujuan'],
                    'target_tabungan'     => $data['target_tabungan'],
                ]);
            }

            $ibadahAccount = $akunSimpanan->fresh()->ibadah;

            if ($ibadahAccount && $akunSimpanan->saldo >= $ibadahAccount->target_tabungan) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.',
                ]);
            }
        }

        if ($data['saving_category'] === SavingTypeEnum::TABUNGAN_BERJANGKA->value) {
            $jatuhTempo = $akunSimpanan->created_at->copy()->addMonths($akunSimpanan->berjangka->tenor);

            if (now()->gte($jatuhTempo)) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Tabungan Berjangka sudah jatuh tempo.',
                ]);
            }
        }
    }

    public function createDepositTransaction(array $data, AkunSimpanan $akunSimpanan, Anggota $anggota): TransaksiSimpanan
    {
        return DB::transaction(function () use ($data, $akunSimpanan, $anggota) {
            $akunSimpanan->refresh();
            $newBalance = $akunSimpanan->saldo + $data['amount'];
            $buktiPenyetoran = null;
            if (
                ($data['metode_pembayaran_simpanan'] ?? null) === 'Non-Tunai'
                && isset($data['bukti_penyetoran'])
            ) {
                $buktiPenyetoran = $data['bukti_penyetoran']->store(
                    'bukti_penyetoran',
                    'public'
                );
            }
            $trx = TransaksiSimpanan::create([
                'kode_transaksi_simpanan' => $this->generateTransactionCode($data['saving_category']),
                'nominal_simpanan'              => $data['amount'],
                'saldo_setelah_transaksi'  => $newBalance,
                'tipe_transaksi'           => TransactionTypeEnum::DEPOSIT->value,
                'metode_pembayaran_simpanan'      => $data['metode_pembayaran_simpanan'],
                'deskripsi_simpanan'         => $data['catatan'] ?? 'Penyetoran',
                'tgl_transaksi'           => $data['date'],
                'updated_by'                 => Auth::id(),
                'akun_simpanan_id'          => $akunSimpanan->id,
                'no_rekening' => $data['no_rekening'] ?? null,
                'bukti_penyetoran' => $buktiPenyetoran,
            ]);

            $akunSimpanan->update([
                'saldo' => $akunSimpanan->saldo + $data['amount']
            ]);

            if ($data['saving_category'] === 'Simpanan Pokok') {
                $anggota->update(['status' => MemberStatusEnum::ACTIVE->value]);
            }

            return $trx;
        });
    }

    public function storeReceiptDepositPdf(
        TransaksiSimpanan $transaction,
        array $strukData,
        int $anggotaId
    ): string
    {
        $pdf = Pdf::loadView('exports.deposit_receipt', [
            'struk' => $strukData
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        $directory = 'dokumen_anggota/receipts/' . now()->format('Y-m');
        Storage::disk('public')->makeDirectory($directory);

        $path = $directory . '/struk-deposit-' . $transaction->id . '.pdf';
        Log::info('Receipt path', [
            'transaction_id' => $transaction->id,
            'path' => $path,
        ]);

        Storage::disk('public')->put($path, $pdf->output());

        if (! Storage::disk('public')->exists($path)) {
            throw new \Exception('File tidak berhasil disimpan');
        }

        $transaction->update([
            'struk_simpanan' => $path,
        ]);
        Log::info('Receipt after update', [
            'receipt' => $transaction->fresh()->struk_simpanan,
        ]);

        return $path;
    }
}