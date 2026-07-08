<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PengaturanUmum;
use App\Services\PengaturanUmumService;
use Illuminate\Http\Request;

class PengaturanUmumController extends Controller
{
    public function __construct(private PengaturanUmumService $pengaturanUmumService)
    {
    }

    public function index()
    {
        return inertia('Admin/Settings/Index', [
            'title' => 'Pengaturan Umum',
            'settings' => $this->pengaturanUmumService->formatSettings(),
            'settingsHistory' => $this->pengaturanUmumService->formatSettingsHistory(),
        ]);
    }

    public function store(Request $request)
    {
        $section = $request->string('section')->toString();

        $rules = match ($section) {
            'general' => [
                'tanggal_awal_periode' => ['required', 'date'],
                'tanggal_akhir_periode' => ['required', 'date', 'after_or_equal:tanggal_awal_periode'],
                'status_tutup_buku' => ['required', 'in:open,closed'],
                'period_effective_date' => ['required', 'date'],
            ],
            'points' => [
                'saving_point_amount' => ['required', 'numeric', 'min:1'],
                'saving_point_reward' => ['required', 'numeric', 'min:1'],
                'tgl_diberlakukan' => ['required', 'date'],
                'murabaha_point_amount' => ['required', 'numeric', 'min:1'],
                'murabaha_point_reward' => ['required', 'numeric', 'min:1'],
                'murabaha_effective_date' => ['required', 'date'],
            ],
            'savings' => [
                'saving_pokok_amount' => ['required', 'numeric', 'min:1'],
                'saving_pokok_effective_date' => ['required', 'date'],
                'saving_wajib_amount' => ['required', 'numeric', 'min:1'],
                'saving_wajib_effective_date' => ['required', 'date'],
            ],
            'pembiayaan' => [
                'murabahah_margin_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'tgl_diberlakukan' => ['required', 'date'],
            ],
            default => [],
        };

        if ($rules === []) {
            return redirect()->back()->withErrors(['section' => 'Bagian pengaturan tidak dikenali.']);
        }

        $keys = match ($section) {
            'general' => ['tanggal_awal_periode', 'tanggal_akhir_periode', 'status_tutup_buku'],
            'points' => ['saving_point_amount', 'saving_point_reward', 'murabaha_point_amount', 'murabaha_point_reward'],
            'savings' => ['saving_pokok_amount', 'saving_wajib_amount'],
            'pembiayaan' => ['murabahah_margin_percentage'],
            default => [],
        };

        $hasData = PengaturanUmum::whereIn('key', $keys)->exists();

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

        $this->pengaturanUmumService->storeSettings($section, $validated, $request->user()->id);

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan umum berhasil disimpan.');
    }
}
