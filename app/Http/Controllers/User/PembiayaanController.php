<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pembiayaan;
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

        $pembiayaan = $this->getPersonalpembiayaan($anggota->id, $perPage, $search);
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

    public function getPersonalpembiayaan(string $anggotaId, int $perPage = 10, string $search = '')
    {
        return Pembiayaan::query()
            ->with(['objekPembiayaan.jenisBarang'])
            ->where('anggota_id', $anggotaId)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan', 'Pembayaran Tangguh'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereRaw(
                    'LOWER(kode_pembiayaan) LIKE ?',
                    ['%' . mb_strtolower($search) . '%']
                );
            })
            ->orderByDesc('tgl_akad')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Pembiayaan $pembiayaan) => $this->mapFinancingForList($pembiayaan));
    }

    public function mapFinancingForList(Pembiayaan $pembiayaan): array
    {
        $productName = null;

        if ($pembiayaan->objekPembiayaan) {
            $productName = $pembiayaan->objekPembiayaan->nama_barang;
        }

        return [
            'id' => $pembiayaan->id,
            'transaction_code' => $pembiayaan->kode_pembiayaan,
            'tgl_akad' => $pembiayaan->tgl_akad,
            'product_name' => $productName,
            'status' => $pembiayaan->status,
            'remaining_balance' => 0,
            'loan' => null,
        ];
    }

    public function getActiveFinancing(string $anggotaId): ?array
    {
        $activeFinancingModel = Pembiayaan::query()
            ->with(['objekPembiayaan.jenisBarang'])
            ->where('anggota_id', $anggotaId)
            ->where('status', 'Angsuran Berjalan')
            ->orderByDesc('tgl_akad')
            ->orderByDesc('created_at')
            ->first();

        return $activeFinancingModel ? $this->mapFinancingForList($activeFinancingModel) : null;
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

        $this->pembiayaanService->computePembiayaanSummary($pembiayaan);
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
