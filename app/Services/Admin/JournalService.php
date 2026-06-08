<?php

namespace App\Services\Admin;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Enums\PositionEnum;

class JournalService
{
    public function create(
        array $entries,
        ?string $transactionDate = null,
        ?string $updatedBy = null
    ): string {

        $this->validateEntries($entries);

        return DB::transaction(function () use (
            $entries,
            $transactionDate,
            $updatedBy
        ) {

            $groupId = Str::uuid()->toString();

            foreach ($entries as $entry) {

                JournalEntry::create([
                    'journal_group_id' => $groupId,
                    'no_ref_account'   => $entry['account'],
                    'position'         => $entry['position'],
                    'nominal'          => $entry['nominal'],
                    'transaction_date' => $transactionDate ?? now()->toDateString(),
                    'updated_by'       => $updatedBy,
                ]);
            }

            return $groupId;
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