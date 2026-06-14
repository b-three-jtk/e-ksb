<?php

namespace App\Services\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Enums\AccountCategoryEnum;
use App\Enums\PositionEnum;

class AkunService
{
    public function calculateBalance(string $noRefAccount, string $category): float
    {
        $debit = JournalEntry::where('no_ref_account', $noRefAccount)
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $kredit = JournalEntry::where('no_ref_account', $noRefAccount)
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        return in_array($category, ['Aset', 'Beban'])
            ? $debit - $kredit
            : $kredit - $debit;
    }

    public function getAccountList(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Account::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('no_ref_account', 'like', "%{$filters['search']}%")
                    ->orWhere('account_name', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['jenis_akun'])) {
            $query->where('account_category', $filters['jenis_akun']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortby = 'no_ref_account';

        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sortby, $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString()
            ->through(fn ($akun) => [
                'id'         => $akun->no_ref_account,
                'nomor_akun' => $akun->no_ref_account,
                'nama_akun'  => $akun->account_name,
                'jenis_akun' => $akun->account_category,
                'saldo'      => $this->calculateBalance(
                                    $akun->no_ref_account,
                                    $akun->account_category
                                ),
                'status'     => $akun->status,
            ]);
    }

    public function getAccountSummary(): \Illuminate\Support\Collection
    {
        return collect(AccountCategoryEnum::cases())
            ->map(function ($item) {
                $accounts = Account::where('account_category', $item->value)->get();

                $totalBalance = $accounts->sum(
                    fn ($akun) => $this->calculateBalance(
                        $akun->no_ref_account,
                        $akun->account_category
                    )
                );

                return [
                    'name'    => $item->value,
                    'balance' => $totalBalance,
                ];
            })
            ->values();
    }

    public function createAccount(array $data): Account
    {
        return Account::create([
            'no_ref_account'   => $data['nomor_akun'],
            'account_name'     => $data['nama_akun'],
            'account_category' => $data['jenis_akun'],
            'status'           => 'Aktif',
        ]);
    }

    public function updateStatus(string $id, string $status): Account
    {
        $account = Account::findOrFail($id);
        $account->update(['status' => $status]);
        return $account;
    }
}