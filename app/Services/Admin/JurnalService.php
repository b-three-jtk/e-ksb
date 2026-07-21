<?php

namespace App\Services\Admin;

use App\Enums\PositionEnum;
use App\Models\Jurnal;
use App\Models\DetailJurnal;
use Illuminate\Support\Facades\DB;

class JurnalService
{
    public function create(array $entries, ?string $date = null, ?string $userId = null): string
    {
        $this->validateEntries($entries);

        $txDate = $date ?? now()->toDateString();

        $startDate = \App\Models\PengaturanUmum::where('key', 'tanggal_awal_periode')->latest('tgl_diberlakukan')->value('value');
        $endDate = \App\Models\PengaturanUmum::where('key', 'tanggal_akhir_periode')->latest('tgl_diberlakukan')->value('value');

        if ($startDate && $txDate < $startDate) {
            throw new \Exception("Tanggal transaksi ({$txDate}) tidak boleh kurang dari tanggal awal periode ({$startDate})");
        }
        if ($endDate && $txDate > $endDate) {
            throw new \Exception("Tanggal transaksi ({$txDate}) tidak boleh melebihi tanggal akhir periode ({$endDate})");
        }

        return DB::transaction(function () use ($entries, $txDate, $userId) {

            $journal = Jurnal::create([
                'tgl_transaksi' => $txDate,
                'created_by'    => $userId,
            ]);

            foreach ($entries as $entry) {
                DetailJurnal::create([
                    'jurnal_id' => $journal->id,
                    'no_ref_akun'   => $entry['akun'],
                    'posisi_akun'         => $entry['posisi_akun'],
                    'nominal'          => $entry['nominal'],
                    'updated_by'       => $userId,
                ]);
            }

            return $journal->id;
        });
    }

    private function validateEntries(array $entries): void
    {
        if (empty($entries)) {
            throw new \Exception('Jurnal tidak boleh kosong');
        }

        $debit = collect($entries)
            ->where('posisi_akun', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $credit = collect($entries)
            ->where('posisi_akun', PositionEnum::CREDIT->value)
            ->sum('nominal');

        if (round($debit, 2) !== round($credit, 2)) {
            throw new \Exception(
                'Total debit dan kredit harus seimbang.'
            );
        }
    }
}
