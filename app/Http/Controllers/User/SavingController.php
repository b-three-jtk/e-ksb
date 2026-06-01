<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\User\LedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SavingController extends Controller
{
    public function index(Request $request, LedgerService $ledgerService)
    {
        $userId = auth()->id();
        $month = $request->input('month');
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $query = $ledgerService->buildLedgerTransactionQuery($userId, $month, $search);
        $query->orderBy('transaction_date', 'desc');

        $transactions = $query->paginate($perPage)->withQueryString();
        $transactions->setCollection($ledgerService->transformTransactions($transactions->getCollection(), true));

        $member = auth()->user();
        $memberInfo = [
            'nama' => $member->name,
            'no_anggota' => $member->user_code,
            'status' => $member->status,
            'tanggal_bergabung' => $member->joined_date->format('d F Y'),
        ];

        [$savingSummary, $savingMeta] = $ledgerService->buildSavingSummaryAndMeta($userId);

        return Inertia::render('User/Ledger/List', [
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

    public function export(Request $request, LedgerService $ledgerService)
    {
        $result = $ledgerService->exportLedgerPdf(
            auth()->id(),
            $request->get('month'),
            $request->get('search')
        );

        return $result['pdf']->download($result['filename']);
    }
}