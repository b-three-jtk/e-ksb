<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\User\BukuBesarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SimpananController extends Controller
{
    public function index(Request $request, BukuBesarService $bukuBesarService)
    {
        $userId = auth()->id();
        $month = $request->input('month');
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $query = $bukuBesarService->buildTabunganTransactionQuery($userId, $month, $search);
        $query->orderBy('transaction_date', 'desc');

        $transactions = $query->paginate($perPage)->withQueryString();
        $transactions->setCollection($bukuBesarService->transformTransactions($transactions->getCollection(), true));

        $anggota = auth()->user();
        $memberInfo = [
            'nama' => $anggota->nama,
            'no_anggota' => $anggota->kode_pengguna,
            'status' => $anggota->status,
            'tanggal_bergabung' => $anggota->tgl_bergabung->format('d F Y'),
        ];

        [$savingSummary, $savingMeta] = $bukuBesarService->buildSavingSummaryAndMeta($userId);

        return Inertia::render('User/Tabungan/List', [
            'transactions' => $transactions,
            'memberInfo' => $memberInfo,
            'savings' => $savingSummary,
            'savingMeta' => $savingMeta,
            'filters' => [
                'search' => $search ?? '',
                'month' => $month ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function export(Request $request, BukuBesarService $bukuBesarService)
    {
        $result = $bukuBesarService->exportTabunganPdf(
            auth()->id(),
            $request->get('month'),
            $request->get('search')
        );

        return $result['pdf']->download($result['filename']);
    }
}
