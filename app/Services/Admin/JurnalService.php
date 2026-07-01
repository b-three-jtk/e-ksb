<?php

namespace App\Services\Admin;

use App\Enums\PositionEnum;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class JurnalService
{
    public function create(array $entries, ?string $date = null, ?string $userId = null): string
    {
        $this->validateEntries($entries);

        return DB::transaction(function () use ($entries, $date, $userId) {

            $journal = Journal::create([
                'tgl_transaksi' => $date ?? now()->toDateString(),
                'created_by'    => $userId,
            ]);

            foreach ($entries as $entry) {
                JournalEntry::create([
                    'journal_id'       => $journal->id,
                    'journal_group_id' => $journal->id,
                    'no_ref_account'   => $entry['account'],
                    'position'         => $entry['position'],
                    'nominal'          => $entry['nominal'],
                    'transaction_date' => $date ?? now()->toDateString(),
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
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        $credit = collect($entries)
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        if (round($debit, 2) !== round($credit, 2)) {
            throw new \Exception(
                'Total debit dan kredit harus seimbang.'
            );
        }
    }
}
