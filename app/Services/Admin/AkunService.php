<?php

namespace App\Services\Admin;

use App\Models\Akun;
use App\Models\DetailJurnal;
use App\Enums\AkunCategoryEnum;
use App\Enums\PositionEnum;

class AkunService
{
    public function calculateBalance(string $noRefAkun, string $category): float
    {
        $debit = DetailJurnal::where('no_ref_akun', $noRefAkun)
            ->where('posisi_akun', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $kredit = DetailJurnal::where('no_ref_akun', $noRefAkun)
            ->where('posisi_akun', PositionEnum::CREDIT->value)
            ->sum('nominal');

        return in_array($category, ['Aset', 'Beban'])
            ? $debit - $kredit
            : $kredit - $debit;
    }

    public function getAkunList(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Akun::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('no_ref_akun', 'like', "%{$filters['search']}%")
                    ->orWhere('nama_akun', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['jenis_akun'])) {
            $query->where('kategori_akun', $filters['jenis_akun']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortby = 'no_ref_akun';

        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sortby, $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString()
            ->through(fn ($akun) => [
                'id'         => $akun->no_ref_akun,
                'nomor_akun' => $akun->no_ref_akun,
                'nama_akun'  => $akun->nama_akun,
                'jenis_akun' => $akun->kategori_akun,
                'saldo'      => $this->calculateBalance(
                                    $akun->no_ref_akun,
                                    $akun->kategori_akun
                                ),
                'status'     => $akun->status,
            ]);
    }

    public function getAkunSummary(): \Illuminate\Support\Collection
    {
        return collect(AkunCategoryEnum::cases())
            ->map(function ($item) {
                $akun = Akun::where('kategori_akun', $item->value)->get();

                $totalBalance = $akun->sum(
                    fn ($akun) => $this->calculateBalance(
                        $akun->no_ref_akun,
                        $akun->kategori_akun
                    )
                );

                return [
                    'name'    => $item->value,
                    'saldo' => $totalBalance,
                ];
            })
            ->values();
    }

    public function createAkun(array $data): Akun
    {
        return Akun::create([
            'no_ref_akun'   => $data['nomor_akun'],
            'nama_akun'     => $data['nama_akun'],
            'kategori_akun' => $data['jenis_akun'],
            'status'           => 'Aktif',
        ]);
    }

    public function updateStatus(string $id, string $status): Akun
    {
        $akun = Akun::findOrFail($id);
        $akun->update(['status' => $status]);
        return $akun;
    }
}