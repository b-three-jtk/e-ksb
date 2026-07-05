<?php

namespace App\Services;

use App\Models\PengaturanUmum;
use Illuminate\Support\Facades\DB;

class PengaturanUmumService
{
    public const SETTING_MAP = [
        'general' => [
            'tanggal_awal_periode' => [
                'label' => 'Tanggal Awal Periode',
                'deskripsi' => 'Tanggal awal periode keuangan.',
            ],
            'tanggal_akhir_periode' => [
                'label' => 'Tanggal Akhir Periode',
                'deskripsi' => 'Tanggal akhir periode keuangan.',
            ],
            'status_tutup_buku' => [
                'label' => 'Status Tutup Buku',
                'deskripsi' => 'Status dari penutupan buku (open/closed).',
            ],
        ],
        'points' => [
            'saving_point_amount' => [
                'label' => 'Jumlah Simpanan',
                'deskripsi' => 'Penetapan besaran simpanan yang dibutuhkan untuk memperoleh poin.',
            ],
            'saving_point_reward' => [
                'label' => 'Poin yang Diperoleh',
                'deskripsi' => 'Jumlah poin yang diberikan untuk setiap kelipatan simpanan.',
            ],
            'murabaha_point_amount' => [
                'label' => 'Jumlah Margin Murabahah',
                'description' => 'Penetapan besaran margin murabahah yang dibayarkan untuk memperoleh poin.',
            ],
            'murabaha_point_reward' => [
                'label' => 'Poin Margin Murabahah',
                'description' => 'Jumlah poin yang diberikan untuk setiap kelipatan margin murabahah dibayarkan.',
            ],
        ],
        'savings' => [
            'saving_pokok_amount' => [
                'label' => 'Simpanan Pokok',
                'deskripsi' => 'Nominal simpanan pokok anggota.',
            ],
            'saving_wajib_amount' => [
                'label' => 'Simpanan Wajib',
                'deskripsi' => 'Nominal simpanan wajib anggota.',
            ],
        ],
        'pembiayaan' => [
            'murabahah_margin_percentage' => [
                'label' => 'Persentase Margin',
                'deskripsi' => 'Persentase margin pembiayaan murabahah.',
            ],
        ],
    ];

    public function getSettingValue(string $key): float
    {
        return (float) PengaturanUmum::where('key', $key)
            ->latest('tgl_diberlakukan')
            ->value('value') ?? 0;
    }

    public function formatSettings(): array
    {
        $records = $this->getAllSettings()->groupBy('key');
        $settings = [];

        foreach (self::SETTING_MAP as $section => $items) {
            foreach ($items as $key => $meta) {
                $setting = $records->get($key)?->first();

                $settings[$section][$key] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'deskripsi' => $meta['deskripsi'],
                    'value' => $setting?->value,
                    'tgl_diberlakukan' => $setting?->tgl_diberlakukan?->toDateString(),
                    'updated_at' => $setting?->updated_at?->toDateTimeString(),
                    'updated_by' => $setting?->updatedBy?->nama,
                ];
            }
        }

        return $settings;
    }

    public function formatSettingsHistory(): array
    {
        $history = [];
        $records = $this->getAllSettings();

        foreach ($records as $record) {
            $section = $this->findSettingSection($record->key);
            if ($section === null) {
                continue;
            }

            $history[$section][] = [
                'id' => $record->id,
                'key' => $record->key,
                'label' => self::SETTING_MAP[$section][$record->key]['label'],
                'value' => $record->value,
                'tgl_diberlakukan' => $record->tgl_diberlakukan?->toDateString(),
                'updated_at' => $record->updated_at?->toDateTimeString(),
                'updated_by' => $record->updatedBy?->nama,
            ];
        }

        return $history;
    }

    public function getAllSettings()
    {
        $allKeys = [];

        foreach (self::SETTING_MAP as $items) {
            $allKeys = array_merge($allKeys, array_keys($items));
        }

        return PengaturanUmum::query()
            ->with(['updatedBy:id,nama'])
            ->whereIn('key', $allKeys)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findSettingSection(string $key): ?string
    {
        foreach (self::SETTING_MAP as $section => $items) {
            if (array_key_exists($key, $items)) {
                return $section;
            }
        }

        return null;
    }

    public function saveSettingGroup(array $items, string $userId): void
    {
        foreach ($items as $key => $payload) {
            PengaturanUmum::query()->create([
                'key' => $key,
                'value' => $payload['value'],
                'tgl_diberlakukan' => $payload['tgl_diberlakukan'],
                'deskripsi' => $payload['deskripsi'],
                'updated_by' => $userId,
            ]);
        }
    }

    public function storeSettings(string $section, array $validated, string $userId): void
    {
        DB::transaction(function () use ($section, $validated, $userId): void {
            match ($section) {
                'general' => $this->saveSettingGroup([
                    'tanggal_awal_periode' => [
                        'value' => $validated['tanggal_awal_periode'],
                        'tgl_diberlakukan' => $validated['period_effective_date'],
                        'deskripsi' => self::SETTING_MAP['general']['tanggal_awal_periode']['deskripsi'],
                    ],
                    'tanggal_akhir_periode' => [
                        'value' => $validated['tanggal_akhir_periode'],
                        'tgl_diberlakukan' => $validated['period_effective_date'],
                        'deskripsi' => self::SETTING_MAP['general']['tanggal_akhir_periode']['deskripsi'],
                    ],
                    'status_tutup_buku' => [
                        'value' => $validated['status_tutup_buku'],
                        'tgl_diberlakukan' => $validated['period_effective_date'],
                        'deskripsi' => self::SETTING_MAP['general']['status_tutup_buku']['deskripsi'],
                    ],
                ], $userId),
                'points' => $this->saveSettingGroup([
                    'saving_point_amount' => [
                        'value' => $validated['saving_point_amount'],
                        'tgl_diberlakukan' => $validated['tgl_diberlakukan'],
                        'deskripsi' => self::SETTING_MAP['points']['saving_point_amount']['deskripsi'],
                    ],
                    'saving_point_reward' => [
                        'value' => $validated['saving_point_reward'],
                        'tgl_diberlakukan' => $validated['tgl_diberlakukan'],
                        'deskripsi' => self::SETTING_MAP['points']['saving_point_reward']['deskripsi'],
                    ],
                    'murabaha_point_amount' => [
                        'value' => $validated['murabaha_point_amount'],
                        'effective_date' => $validated['murabaha_effective_date'],
                        'description' => self::SETTING_MAP['points']['murabaha_point_amount']['description'],
                    ],
                    'murabaha_point_reward' => [
                        'value' => $validated['murabaha_point_reward'],
                        'effective_date' => $validated['murabaha_effective_date'],
                        'description' => self::SETTING_MAP['points']['murabaha_point_reward']['description'],
                    ],
                ], $userId),
                'savings' => $this->saveSettingGroup([
                    'saving_pokok_amount' => [
                        'value' => $validated['saving_pokok_amount'],
                        'tgl_diberlakukan' => $validated['saving_pokok_effective_date'],
                        'deskripsi' => self::SETTING_MAP['savings']['saving_pokok_amount']['deskripsi'],
                    ],
                    'saving_wajib_amount' => [
                        'value' => $validated['saving_wajib_amount'],
                        'tgl_diberlakukan' => $validated['saving_wajib_effective_date'],
                        'deskripsi' => self::SETTING_MAP['savings']['saving_wajib_amount']['deskripsi'],
                    ],
                ], $userId),
                'pembiayaan' => $this->saveSettingGroup([
                    'murabahah_margin_percentage' => [
                        'value' => $validated['murabahah_margin_percentage'],
                        'tgl_diberlakukan' => $validated['tgl_diberlakukan'],
                        'deskripsi' => self::SETTING_MAP['pembiayaan']['murabahah_margin_percentage']['deskripsi'],
                    ],
                ], $userId),
            };
        });
    }
}
