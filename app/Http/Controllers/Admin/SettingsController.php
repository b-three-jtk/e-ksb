<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    private const SETTING_MAP = [
        'points' => [
            'saving_point_amount' => [
                'label' => 'Jumlah Simpanan',
                'description' => 'Penetapan besaran simpanan yang dibutuhkan untuk memperoleh poin.',
            ],
            'saving_point_reward' => [
                'label' => 'Poin yang Diperoleh',
                'description' => 'Jumlah poin yang diberikan untuk setiap kelipatan simpanan.',
            ],
        ],
        'savings' => [
            'saving_pokok_amount' => [
                'label' => 'Simpanan Pokok',
                'description' => 'Nominal simpanan pokok anggota.',
            ],
            'saving_wajib_amount' => [
                'label' => 'Simpanan Wajib',
                'description' => 'Nominal simpanan wajib anggota.',
            ],
        ],
        'financing' => [
            'murabahah_margin_percentage' => [
                'label' => 'Persentase Margin',
                'description' => 'Persentase margin pembiayaan murabahah.',
            ],
        ],
    ];

    public function index()
    {
        return inertia('Admin/Settings/Index', [
            'title' => 'Pengaturan Umum',
            'settings' => $this->formatSettings(),
            'settingsHistory' => $this->formatSettingsHistory(),
        ]);
    }

    public function store(Request $request)
    {
        $section = $request->string('section')->toString();

        $rules = match ($section) {
            'points' => [
                'saving_point_amount' => ['required', 'numeric', 'min:1'],
                'saving_point_reward' => ['required', 'numeric', 'min:1'],
                'effective_date' => ['required', 'date'],
            ],
            'savings' => [
                'saving_pokok_amount' => ['required', 'numeric', 'min:1'],
                'saving_pokok_effective_date' => ['required', 'date'],
                'saving_wajib_amount' => ['required', 'numeric', 'min:1'],
                'saving_wajib_effective_date' => ['required', 'date'],
            ],
            'financing' => [
                'murabahah_margin_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'effective_date' => ['required', 'date'],
            ],
            default => [],
        };

        if ($rules === []) {
            return redirect()->back()->withErrors(['section' => 'Bagian pengaturan tidak dikenali.']);
        }

        $keys = match ($section) {
            'points' => ['saving_point_amount', 'saving_point_reward'],
            'savings' => ['saving_pokok_amount', 'saving_wajib_amount'],
            'financing' => ['murabahah_margin_percentage'],
            default => [],
        };

        $hasData = GlobalSetting::whereIn('key', $keys)->exists();

        if ($hasData) {
            if (!$request->user()->can('edit_pengaturan')) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah pengaturan ini.');
            }
        } else {
            if (!$request->user()->can('create_pengaturan')) {
                abort(403, 'Anda tidak memiliki akses untuk membuat pengaturan ini.');
            }
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($section, $validated, $request): void {
            $userId = $request->user()->id;

            match ($section) {
                'points' => $this->saveSettingGroup([
                    'saving_point_amount' => [
                        'value' => $validated['saving_point_amount'],
                        'effective_date' => $validated['effective_date'],
                        'description' => self::SETTING_MAP['points']['saving_point_amount']['description'],
                    ],
                    'saving_point_reward' => [
                        'value' => $validated['saving_point_reward'],
                        'effective_date' => $validated['effective_date'],
                        'description' => self::SETTING_MAP['points']['saving_point_reward']['description'],
                    ],
                ], $userId),
                'savings' => $this->saveSettingGroup([
                    'saving_pokok_amount' => [
                        'value' => $validated['saving_pokok_amount'],
                        'effective_date' => $validated['saving_pokok_effective_date'],
                        'description' => self::SETTING_MAP['savings']['saving_pokok_amount']['description'],
                    ],
                    'saving_wajib_amount' => [
                        'value' => $validated['saving_wajib_amount'],
                        'effective_date' => $validated['saving_wajib_effective_date'],
                        'description' => self::SETTING_MAP['savings']['saving_wajib_amount']['description'],
                    ],
                ], $userId),
                'financing' => $this->saveSettingGroup([
                    'murabahah_margin_percentage' => [
                        'value' => $validated['murabahah_margin_percentage'],
                        'effective_date' => $validated['effective_date'],
                        'description' => self::SETTING_MAP['financing']['murabahah_margin_percentage']['description'],
                    ],
                ], $userId),
            };
        });

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    private function formatSettings(): array
    {
        $records = $this->getAllSettings()->groupBy('key');
        $settings = [];

        foreach (self::SETTING_MAP as $section => $items) {
            foreach ($items as $key => $meta) {
                $setting = $records->get($key)?->first();

                $settings[$section][$key] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'value' => $setting?->value,
                    'effective_date' => $setting?->effective_date?->toDateString(),
                    'updated_at' => $setting?->updated_at?->toDateTimeString(),
                    'updated_by' => $setting?->updatedBy?->name,
                ];
            }
        }

        return $settings;
    }

    private function formatSettingsHistory(): array
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
                'effective_date' => $record->effective_date?->toDateString(),
                'updated_at' => $record->updated_at?->toDateTimeString(),
                'updated_by' => $record->updatedBy?->name,
            ];
        }

        return $history;
    }

    private function getAllSettings()
    {
        $allKeys = [];

        foreach (self::SETTING_MAP as $items) {
            $allKeys = array_merge($allKeys, array_keys($items));
        }

        return GlobalSetting::query()
            ->with(['updatedBy:id,name'])
            ->whereIn('key', $allKeys)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function findSettingSection(string $key): ?string
    {
        foreach (self::SETTING_MAP as $section => $items) {
            if (array_key_exists($key, $items)) {
                return $section;
            }
        }

        return null;
    }

    private function saveSettingGroup(array $items, string $userId): void
    {
        foreach ($items as $key => $payload) {
            GlobalSetting::query()->create([
                'key' => $key,
                'value' => $payload['value'],
                'effective_date' => $payload['effective_date'],
                'description' => $payload['description'],
                'updated_by' => $userId,
            ]);
        }
    }
}