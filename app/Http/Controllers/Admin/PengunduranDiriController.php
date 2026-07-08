<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PengunduranDiriService;
use Illuminate\Http\Request;

class PengunduranDiriController extends Controller
{
    public function __construct(private PengunduranDiriService $service){}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $per_page = $request->input('per_page', 10);
        $sort_by = $request->input('sort_by', 'created_at');
        $sort_dir = $request->input('sort_dir', 'desc');

        // Paginate results
        $anggota = $this->service->getSemuaPengunduranDiri($search, $per_page, $sort_by, $sort_dir);

        return inertia('Admin/User/Resignation/List', [
            'anggota' => $anggota,
            'filters' => [
                'search' => $search,
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function validation($id)
    {
        $data = [];
        $data['user'] = $this->service->getAnggotaMengundurkanDiri($id);

        $resignationDoc = $data['user']->anggota->dokumenAnggota?->first()?->lampiran_dokumen ? asset('storage/' . $data['user']->anggota->dokumenAnggota->first()->lampiran_dokumen) : null;

        $totalSavings = $data['user']->anggota->akunSimpanan()->sum('saldo');
        $totalObligation = $this->service->getTotalKewajiban($data['user']);

        return inertia('Admin/User/Resignation/Validation', [
            'data' => [
                ...$data['user']->toArray(),
                'resignation_doc' => $resignationDoc,
                'total_savings' => $totalSavings,
                'total_obligations' => $totalObligation,
            ]
        ]);
    }

    public function validate(string $id)
    {
        $user = $this->service->getAnggotaMengundurkanDiri($id);
        $this->service->updateStatusAnggota($user);

        return to_route('admin.resignations.index')->with([
            'success' => 'Pengunduran diri berhasil divalidasi.',
            'resignation_info' => [
                'nama'      => $user->nama,
                'kode_pengguna' => $user->kode_pengguna,
                'phone'     => $user->no_telp,
            ],
        ]);
    }
}
