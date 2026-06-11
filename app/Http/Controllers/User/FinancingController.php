<?php

namespace App\Http\Controllers\User;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
use App\Services\Admin\FinancingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinancingController extends Controller
{
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

        $query = Financing::query()
            ->with(['financingItem.productType'])
            ->where('member_id', $member->id)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereRaw(
                    'LOWER(financing_transaction_code) LIKE ?',
                    ['%' . mb_strtolower($search) . '%']
                );
            })


            ->orderByDesc('akad_date')
            ->orderByDesc('created_at');

        $financings = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Financing $financing) => $this->mapFinancingForList($financing));

        $activeFinancingModel = Financing::query()
            ->with(['financingItem.productType'])
            ->where('member_id', $member->id)
            ->where('status', 'Angsuran Berjalan')
            ->orderByDesc('akad_date')
            ->orderByDesc('created_at')
            ->first();

        return inertia('User/Financing/List', [
            'financings' => $financings,
            'activeFinancing' => $activeFinancingModel ? $this->mapFinancingForList($activeFinancingModel) : null,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function mapFinancingForList($financing): array
    {
        $productName = null;

        if ($financing->financingItem && $financing->financingItem) {
            $productName = $financing->financingItem->name;
        }

        return [
            'id' => $financing->id,
            'transaction_code' => $financing->financing_transaction_code,
            'akad_date' => $financing->akad_date,
            'product_name' => $productName,
            'status' => $financing->status,
            'remaining_balance' => 0,
            'loan' => null,
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, FinancingService $service)
    {
        $financing = Financing::with([
            'financingItem.productType',
            'financingItem.supplier',
            'installment' => fn($q) => $q->orderBy('installment_no'),
            'installment.payment',
            'collateral',
        ])->findOrFail($id);

        $service->computeFinancingSummary($financing);
        $service->computeNextDueDate($financing);

        $financing->setRelation('installment', $financing->installment->map(function ($item) {
            return [
                'installment_no'              => $item->installment_no,
                'installment_trans_code'      => $item->payment?->installment_trans_code,
                'due_date'                    => $item->due_date,
                'payment_date'               => $item->payment?->payment_date,
                'amount'                     => $item->payment?->nominal,
                'is_early_repayment'         => $item->payment?->is_early_repayment ?? false,
                'installment_payment_receipt' => $item->payment?->installment_payment_receipt,
            ];
        }));

        return inertia('User/Financing/Show', ['data' => $financing]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
