<?php

namespace App\Console\Commands;

use App\Models\GlobalSetting;
use App\Models\PointTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateMonthlySavingPoints extends Command
{
    protected $signature = 'points:calculate-monthly-savings';

    protected $description = 'Calculate and store monthly saving points for members';

    public function handle(): int
    {
        $calculationDate = now()->toDateString();
        $periodDate = now()->copy()->endOfMonth()->toDateString();
        $periodLabel = Carbon::parse($periodDate)->translatedFormat('F Y');

        $savingPointAmount = $this->resolveActiveSettingValue('saving_point_amount', $calculationDate);
        $savingPointReward = $this->resolveActiveSettingValue('saving_point_reward', $calculationDate);

        if ($savingPointAmount === null || $savingPointReward === null) {
            $this->error('Konfigurasi poin simpanan aktif tidak ditemukan untuk periode perhitungan.');

            return self::FAILURE;
        }

        if ($savingPointAmount <= 0 || $savingPointReward <= 0) {
            $this->error('Konfigurasi poin simpanan aktif tidak valid.');

            return self::FAILURE;
        }

        $users = User::query()
            ->whereHas('member.savingAccounts')
            ->with([
                'member.savingAccounts',
                'pointTransactions' => function ($query) use ($periodDate) {
                    $query->whereDate('calculation_period', $periodDate);
                },
            ])
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($user->pointTransactions->isNotEmpty()) {
                $skipped++;
                continue;
            }

            $totalSavings = (float) $user->member->savingAccounts->sum('balance');
            $pointsEarned = (int) floor($totalSavings / $savingPointAmount) * (int) $savingPointReward;

            if ($pointsEarned <= 0) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($user, $periodDate, $periodLabel, $totalSavings, $pointsEarned): void {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'amount_earned' => $pointsEarned,
                    'activity_description' => sprintf(
                        'Perhitungan poin simpanan periode %s dengan total simpanan Rp %s',
                        $periodLabel,
                        number_format($totalSavings, 0, ',', '.')
                    ),
                    'calculation_period' => $periodDate,
                    'saving_balance_snapshot' => $totalSavings,
                ]);
            });

            $created++;
        }

        $this->info(sprintf('Perhitungan poin selesai. Dibuat: %d, dilewati: %d.', $created, $skipped));

        return self::SUCCESS;
    }

    private function resolveActiveSettingValue(string $key, string $periodDate): ?float
    {
        $setting = GlobalSetting::query()
            ->where('key', $key)
            ->whereDate('effective_date', '<=', $periodDate)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if (!$setting) {
            return null;
        }

        return $this->normalizeSettingValue($setting->value, 0);
    }

    private function normalizeSettingValue(mixed $value, float $default): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $stringValue = trim((string) $value);

        if (is_numeric($stringValue)) {
            return (float) $stringValue;
        }

        $sanitized = preg_replace('/[^\d,.-]/', '', $stringValue) ?? '';

        if ($sanitized === '') {
            return $default;
        }

        $lastComma = strrpos($sanitized, ',');
        $lastDot = strrpos($sanitized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $sanitized = str_replace('.', '', $sanitized);
                $sanitized = str_replace(',', '.', $sanitized);
            } else {
                $sanitized = str_replace(',', '', $sanitized);
            }
        } elseif ($lastComma !== false) {
            $sanitized = str_replace('.', '', $sanitized);
            $sanitized = str_replace(',', '.', $sanitized);
        } else {
            $sanitized = str_replace(',', '', $sanitized);
        }

        return (float) $sanitized;
    }
}