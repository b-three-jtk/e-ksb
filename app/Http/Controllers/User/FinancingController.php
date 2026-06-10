<?php

namespace App\Http\Controllers\User;

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
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
    public function show(string $id)
    {
        $user = auth()->user();
        $member = $user?->member;

        if (!$member) {
            abort(404);
        }

        $financing = Financing::with(['financingItem.productType', 'installment.payment', 'financingItem.supplier', 'collateral'])
        ->where('member_id', $member->id)
        ->findOrFail($id);
        $financing->total_price = ($financing->cost_price ?? 0) + ($financing->margin_amount ?? 0) - ($financing->down_payment ?? 0);

        $installment = $financing->installment;

        if ($installment && $installment->count() > 0) {
            $paid_count = $installment->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();
            $total_paid = $financing->tenor > 0
                ? $paid_count * (($financing->margin_amount ?? 0) + ($financing->cost_price ?? 0) - ($financing->down_payment ?? 0)) / $financing->tenor
                : 0;
            $financing->remaining_balance = $financing->total_price - $total_paid;
            $financing->total_paid = $total_paid;

            if ($financing->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
        } else {
            $financing->total_paid = 0;
            if ($installment && $financing->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
            $financing->remaining_balance = $financing->total_price;
        }

        if ($installment && $financing->akad_date) {
            $paid_count = $installment ? $installment->count() : 0;
            if ($paid_count < $financing->tenor) {
                $financing->next_due_date = Carbon::parse($financing->akad_date)
                    ->addMonthsNoOverflow($paid_count + 1)
                    ->format('Y-m-d');
            } else {
                $financing->next_due_date = null;
            }
        }

        return inertia('User/Financing/Show', [
            'data' => $financing
        ]);
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
