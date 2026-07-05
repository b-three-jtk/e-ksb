<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PembiayaanService;
use Illuminate\Http\Request;

class PembiayaanController extends Controller
{
    public function __construct(private PembiayaanService $pembiayaanService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $anggota = $user->anggota;

        if (!$anggota) {
            return inertia('User/Financing/List', [
                'pembiayaan' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1,
                    'links' => [],
                ],
                'activeFinancing' => null,
                'filters' => [
                    'search' => '',
                    'per_page' => 10,
                ],
            ]);
        }

        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $search = trim((string) $request->input('search', ''));

        $pembiayaan = $this->pembiayaanService->getPersonalpembiayaan($anggota->id, $perPage, $search);
        $activeFinancing = $this->pembiayaanService->getActiveFinancing($anggota->id);

        return inertia('User/Financing/List', [
            'pembiayaan' => $pembiayaan,
            'activeFinancing' => $activeFinancing,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $anggota = $user->anggota;
        $pembiayaan = $this->pembiayaanService->getPembiayaanById($id);

        if ($pembiayaan->anggota_id !== $anggota->id) {
            abort(403, 'Anggota tidak memiliki akses ke pembiayaan ini.');
        }

        $this->pembiayaanService->computepembiayaanummary($pembiayaan);
        $this->pembiayaanService->computeNextDueDate($pembiayaan);

        $pembiayaan->setRelation('angsuran', $pembiayaan->angsuran->map(function ($item) {
            return [
                'angsuran_ke'              => $item->angsuran_ke,
                'kode_transaksi_pembayaran'      => $item->payment?->kode_transaksi_pembayaran,
                'tgl_jatuh_tempo'                    => $item->tgl_jatuh_tempo,
                'tgl_pembayaran'               => $item->payment?->tgl_pembayaran,
                'nominal_angsuran'                     => $item->payment?->jumlah_angsuran_dibayar,
                'is_pelunasan_lebih_cepat'         => $item->payment?->is_pelunasan_lebih_cepat ?? false,
                'struk_pembayaran' => $item->payment?->struk_pembayaran ? asset('storage/' . $item->payment->struk_pembayaran) : null,
            ];
        }));

        return inertia('User/Financing/Show', ['data' => $pembiayaan]);
    }
}
