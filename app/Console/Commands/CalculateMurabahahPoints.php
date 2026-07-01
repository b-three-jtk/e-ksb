<?php

namespace App\Console\Commands;

use App\Models\GlobalSetting;
use App\Models\InstallmentPaymentTransaction;
use App\Models\PointTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateMurabahahPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'points:calculate-murabahah-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store murabahah points for members';

    /**
     * Execute the console command.
     */
public function handle(): int
    {
        $calculationDate = now()->toDateString();

        $startDate = $this->resolveStringSettingValue('tanggal_awal_periode', $calculationDate);
        $endDate = $this->resolveStringSettingValue('tanggal_akhir_periode', $calculationDate);
        $statusTutupBuku = $this->resolveStringSettingValue('status_tutup_buku', $calculationDate);

        if (!$startDate || !$endDate || !$statusTutupBuku) {
            $this->error('Konfigurasi periode atau status tutup buku tidak ditemukan.');
            return self::FAILURE;
        }

        if (strtolower($statusTutupBuku) !== 'closed') {
            $this->warn('Status tutup buku saat ini belum closed. Perhitungan poin Murabahah dibatalkan.');
            return self::FAILURE;
        }

        $pointAmount = $this->resolveActiveSettingValue('murabaha_point_amount', $calculationDate);
        $pointReward = $this->resolveActiveSettingValue('murabaha_point_reward', $calculationDate);

        if ($pointAmount === null || $pointReward === null || $pointAmount <= 0 || $pointReward <= 0) {
            $this->error('Konfigurasi poin murabahah aktif tidak valid atau tidak ditemukan.');
            return self::FAILURE;
        }

        $periodLabel = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');

        $userMargins = InstallmentPaymentTransaction::query()
            ->join('installments', 'installment_payment_transactions.installment_id', '=', 'installments.id')
            ->join('financings', 'installments.financing_id', '=', 'financings.id')
            ->join('members', 'financings.member_id', '=', 'members.id')
            ->whereDate('installment_payment_transactions.payment_date', '>=', $startDate)
            ->whereDate('installment_payment_transactions.payment_date', '<=', $endDate)
            ->select('members.user_id', DB::raw('SUM(installment_payment_transactions.margin_amount) as total_margin'))
            ->groupBy('members.user_id')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($userMargins as $data) {
            $totalMargin = (float) $data->total_margin;
            $pointsEarned = (int) floor($totalMargin / $pointAmount) * (int) $pointReward;

            if ($pointsEarned <= 0) {
                $skipped++;
                continue;
            }

            $hasPoint = PointTransaction::query()
                ->where('user_id', $data->user_id)
                ->whereDate('calculation_period', $endDate)
                ->where('activity_description', 'LIKE', '%murabahah%')
                ->exists();

            if ($hasPoint) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($data, $endDate, $periodLabel, $totalMargin, $pointsEarned): void {
                PointTransaction::create([
                    'user_id' => $data->user_id,
                    'amount_earned' => $pointsEarned,
                    'activity_description' => sprintf(
                        'Perhitungan poin murabahah periode %s dengan total margin Rp %s',
                        $periodLabel,
                        number_format($totalMargin, 0, ',', '.')
                    ),
                    'calculation_period' => $endDate,
                ]);
            });

            $created++;
        }

        $this->info(sprintf('Perhitungan poin murabahah selesai. Dibuat: %d, dilewati: %d.', $created, $skipped));

        return self::SUCCESS;
    }

    /**
     * Resolves numeric setting values.
     */
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

    /**
     * Resolves string/date setting values without normalizing to float.
     */
    private function resolveStringSettingValue(string $key, string $periodDate): ?string
    {
        $setting = GlobalSetting::query()
            ->where('key', $key)
            ->whereDate('effective_date', '<=', $periodDate)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        return $setting ? $setting->value : null;
    }

    /**
     * Normalizes a setting value to a float.
     */
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
