<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Financing;
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
        $member = $user->member;

        if (!$member) {
            return inertia('User/Financing/List', [
                'financings' => [
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

        $financings = $this->pembiayaanService->getPersonalFinancings($member->id, $perPage, $search);
        $activeFinancing = $this->pembiayaanService->getActiveFinancing($member->id);

        return inertia('User/Financing/List', [
            'financings' => $financings,
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
        $financing = $this->pembiayaanService->getPembiayaanById($id);

        $this->pembiayaanService->computeFinancingSummary($financing);
        $this->pembiayaanService->computeNextDueDate($financing);

        $financing->setRelation('installment', $financing->installment->map(function ($item) {
            return [
                'installment_no'              => $item->installment_no,
                'installment_trans_code'      => $item->payment?->installment_trans_code,
                'due_date'                    => $item->due_date,
                'payment_date'               => $item->payment?->payment_date,
                'amount'                     => $item->payment?->nominal,
                'is_early_repayment'         => $item->payment?->is_early_repayment ?? false,
                'installment_payment_receipt' => $item->payment?->installment_payment_receipt ? asset('storage/' . $item->payment->installment_payment_receipt) : null,
            ];
        }));

        return inertia('User/Financing/Show', ['data' => $financing]);
    }
}
