<?php

namespace App\Services\Admin;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\PositionEnum;

class JournalService
{
    public function create(array $entries, ?string $date = null, ?string $userId = null): string
    {
        $this->validateEntries($entries);

        return DB::transaction(function () use ($entries, $date, $userId) {

            $journal = \App\Models\Journal::create([
                'tgl_transaksi' => $date ?? now()->toDateString(),
                'created_by'    => $userId,
            ]);

            foreach ($entries as $entry) {
                \App\Models\JournalEntry::create([
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