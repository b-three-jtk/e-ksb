<?php

namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\GlobalSetting;
use App\Models\BerjangkaAccount;
use App\Models\IbadahAccount;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
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
        return (float) GlobalSetting::where('key', $key)
            ->where('effective_date', '<=', now())
            ->orderByDesc('effective_date')
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
        $lastNo  = SavingAccount::where('saving_account_code', 'like', "{$prefix}{$yymm}%")
            ->count();
        $seq     = str_pad((string)($lastNo + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$yymm}{$seq}";
    }

    public function generateTransactionCode(string $category): string
    {
        $prefix  = $this->getTrxCodePrefix($category);
        $yymm    = now()->format('ym'); // e.g. 2506
        $lastNo  = SavingTransaction::where('saving_transaction_code', 'like', "{$prefix}{$yymm}%")
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

        $query = SavingTransaction::with([
            'savingAccount.member.user',
            'savingAccount',
        ]);

        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('savingAccount.member', function ($q) {
                $q->where('pj_user_id', Auth::id());
            });
        }

        return $query
            ->when($search, function ($q) use ($search) {
                $q->whereHas('savingAccount.member.user', function ($m) use ($search) {
                    $m->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('user_code', 'like', "%{$search}%");
                });
            })
            ->when(isset($typeMap[$tab]), function ($q) use ($typeMap, $tab) {
                $q->whereHas('savingAccount', function ($sa) use ($typeMap, $tab) {
                    $sa->where('saving_type', $typeMap[$tab]);
                });
            })
            ->when($tab === 'simpanan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('saving_type', [
                        SavingTypeEnum::SIMPANAN_POKOK->value,
                        SavingTypeEnum::SIMPANAN_WAJIB->value,
                    ]);
                });
            })
            ->when($tab === 'tabungan', function ($q) {
                $q->whereHas('savingAccount', function ($sa) {
                    $sa->whereIn('saving_type', [
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
        $sortBy   = in_array($request->input('sort_by'), ['transaction_date']) ? $request->input('sort_by') : 'transaction_date';
        $sortDir  = $request->input('sort_dir', 'desc');

        $transactions = $this->buildBaseQuery($request)
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($trx) => [
                'id'           => $trx->id,
                'no_transaksi' => $trx->saving_transaction_code,
                'tanggal'      => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                'anggota'      => $trx->savingAccount->member->user->user_code
                                . ' - '
                                . $trx->savingAccount->member->user->name,
                'nominal'      => $trx->transaction_type === TransactionTypeEnum::WITHDRAWAL->value
                                ? -$trx->saving_amount
                                : $trx->saving_amount,
                'produk'       => $trx->savingAccount->saving_type,
                'jenis'        => $trx->transaction_type,
            ]);

        $summaryBase     = $this->buildBaseQuery($request);
        $totalMasuk      = (clone $summaryBase)->where('transaction_type', 'Penyetoran')->sum('saving_amount');
        $totalKeluar     = (clone $summaryBase)->where('transaction_type', 'Penarikan')->sum('saving_amount');
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
        $query = Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
            MemberStatusEnum::PAYMENT_PENDING->value,
        ])
        ->when(
            Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value),
            fn($q) => $q->where('pj_user_id', Auth::id())
        )
        ->with([
            'user:id,user_code,name',
            'savingAccounts.ibadah',
            'savingAccounts.berjangka',
        ]);

        return $query->get()->map(fn($member) => [
            'id'            => $member->id,
            'user_code'     => $member->user->user_code,
            'name'          => $member->user->name,
            'status'        => $member->status,
            'savingAccounts' => $member->savingAccounts
                ->filter(function ($acc) {
                    if ($acc->saving_type === 'Tabungan Ibadah')    return $acc->ibadah;
                    if ($acc->saving_type === 'Tabungan Berjangka') return $acc->berjangka;
                    return true;
                })
                ->map(fn($acc) => [
                    'id'            => $acc->id,
                    'type'          => $acc->saving_type ?? null,
                    'purpose'       => $acc->berjangka?->purpose ?? $acc->ibadah?->purpose,
                    'balance'       => $acc->balance ?? 0,
                    'target_amount' => $acc->ibadah?->target_amount,
                    'matured_at'    => $acc->berjangka
                        ? $acc->created_at->copy()->addMonths($acc->berjangka->tenor)->format('d M Y')
                        : null,
                    'is_frozen'     => $acc->ibadah
                        ? $acc->balance >= $acc->ibadah->target_amount
                        : false,
                    'is_matured'    => $acc->berjangka
                        ? now()->gte($acc->created_at->copy()->addMonths($acc->berjangka->tenor))
                        : false,
                ])
                ->values()
                ->toArray(),
        ]);
    }

    // Store Deposit

    public function resolveOrCreateSavingAccount(array $data, Member $member): SavingAccount
    {
        if (filled($data['saving_account_id'] ?? null)) {
            return SavingAccount::where('id', $data['saving_account_id'])
                ->where('member_id', $member->id)
                ->firstOrFail();
        }

        if (in_array($data['saving_category'], [
            SavingTypeEnum::SIMPANAN_POKOK->value,
            SavingTypeEnum::SIMPANAN_WAJIB->value,
            SavingTypeEnum::TABUNGAN_ANGGOTA->value,
        ])) {
            return SavingAccount::firstOrCreate(
                ['member_id' => $member->id, 'saving_type' => $data['saving_category']],
                ['saving_account_code' => $this->generateAccountCode($data['saving_category'])]
            );
        }

        return SavingAccount::create([
            'member_id'           => $member->id,
            'saving_type'         => $data['saving_category'],
            'saving_account_code' => $this->generateAccountCode($data['saving_category']),
        ]);
    }

    public function validateDepositRules(array $data, SavingAccount $savingAccount, Member $member): void
    {
        $isNewAccount = empty($data['saving_account_id']);
        if ($isNewAccount && $data['saving_category'] === 'Tabungan Berjangka') {
            if (empty($data['tenor_months'])) {
                throw ValidationException::withMessages([
                    'tenor_months' => 'Jatuh tempo wajib diisi untuk tabungan berjangka baru.',
                ]);
            }

            BerjangkaAccount::create([
                'saving_account_id' => $savingAccount->id,
                'purpose'           => $data['purpose'] ?? null,
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

            if (SavingTransaction::where('saving_account_id', $savingAccount->id)
                ->where('transaction_type', TransactionTypeEnum::DEPOSIT->value)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Simpanan Pokok hanya boleh dibayar sekali.',
                ]);
            }

            if ($member->status !== MemberStatusEnum::PAYMENT_PENDING->value) {
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
            if ($savingAccount->wasRecentlyCreated) {
                if (empty($data['target_amount'])) {
                    throw ValidationException::withMessages([
                        'target_amount' => 'Target tabungan wajib diisi.',
                    ]);
                }

                IbadahAccount::create([
                    'saving_account_id' => $savingAccount->id,
                    'purpose'           => $data['purpose'],
                    'target_amount'     => $data['target_amount'],
                ]);
            }

            $ibadahAccount = $savingAccount->fresh()->ibadah;

            if ($ibadahAccount && $savingAccount->balance >= $ibadahAccount->target_amount) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Tabungan Ibadah sudah mencapai target dan dibekukan.',
                ]);
            }
        }

        if ($data['saving_category'] === SavingTypeEnum::TABUNGAN_BERJANGKA->value) {
            $jatuhTempo = $savingAccount->created_at->copy()->addMonths($savingAccount->berjangka->tenor);

            if (now()->gte($jatuhTempo)) {
                throw ValidationException::withMessages([
                    'saving_category' => 'Tabungan Berjangka sudah jatuh tempo.',
                ]);
            }
        }
    }

    public function createDepositTransaction(array $data, SavingAccount $savingAccount, Member $member): SavingTransaction
    {
        return DB::transaction(function () use ($data, $savingAccount, $member) {
            $savingAccount->refresh();
            $newBalance = $savingAccount->balance + $data['amount'];
            $trx = SavingTransaction::create([
                'saving_transaction_code' => $this->generateTransactionCode($data['saving_category']),
                'saving_amount'              => $data['amount'],
                'balance_after_transaction'  => $newBalance,
                'transaction_type'           => TransactionTypeEnum::DEPOSIT->value,
                'saving_payment_method'      => $data['saving_payment_method'],
                'saving_description'         => $data['notes'] ?? 'Penyetoran',
                'transaction_date'           => $data['date'],
                'updated_by'                 => Auth::id(),
                'saving_account_id'          => $savingAccount->id,
            ]);

            $savingAccount->update([
                'balance' => $savingAccount->balance + $data['amount']
            ]);

            if ($data['saving_category'] === 'Simpanan Pokok') {
                $member->update(['status' => MemberStatusEnum::ACTIVE->value]);
            }

            return $trx;
        });
    }

    public function storeReceiptDepositPdf(
        SavingTransaction $transaction,
        array $strukData,
        int $memberId
    ): string
    {
        $pdf = Pdf::loadView('exports.deposit_receipt', [
            'struk' => $strukData
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        $directory = 'member_docs/receipts/' . now()->format('Y-m');
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
            'saving_transaction_receipt' => $path,
        ]);
        Log::info('Receipt after update', [
            'receipt' => $transaction->fresh()->saving_transaction_receipt,
        ]);

        return $path;
    }
}