<?php

namespace App\Http\Controllers\User;

use App\Enums\LoanPaymentScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\Financing;
use Illuminate\Http\Request;

class UserFinancingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $search = trim((string) $request->input('search', ''));
        $productType = trim((string) $request->input('product_type', ''));

        $query = Financing::query()
            ->with(['loan.paymentSchedules'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan'])
            ->when($search !== '', function ($q) use ($search) {
                $searchLower = mb_strtolower($search);
                $q->where(function ($sub) use ($searchLower) {
                    $sub->whereRaw('LOWER(transaction_code) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(product_type) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(status) LIKE ?', ["%{$searchLower}%"]);
                });
            })
            ->when($productType !== '', function ($q) use ($productType) {
                $q->where('product_type', $productType);
            })
            ->orderByDesc('akad_date')
            ->orderByDesc('created_at');

        $productTypes = Financing::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['Lunas', 'Angsuran Berjalan'])
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->select('product_type')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type')
            ->values();

        $financings = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Financing $financing) => $this->mapFinancingForList($financing));

        $activeFinancingModel = Financing::query()
            ->with(['loan.paymentSchedules'])
            ->where('user_id', $user->id)
            ->where('status', 'Angsuran Berjalan')
            ->orderByDesc('akad_date')
            ->orderByDesc('created_at')
            ->first();

        return inertia('User/Financing/List', [
            'financings' => $financings,
            'activeFinancing' => $activeFinancingModel ? $this->mapFinancingForList($activeFinancingModel) : null,
            'productTypes' => $productTypes,
            'filters' => [
                'search' => $search,
                'product_type' => $productType,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function mapFinancingForList(Financing $financing): array
    {
        $remainingBalance = 0;
        $nextDueDate = null;

        if ($financing->loan) {
            $remainingBalance = (float) $financing->loan->remaining_margin + (float) $financing->loan->remaining_principal;

            $scheduled = $financing->loan->paymentSchedules
                ->where('status', LoanPaymentScheduleStatus::SCHEDULED->value)
                ->sortBy('due_date')
                ->first();

            if ($scheduled) {
                $nextDueDate = $scheduled->due_date;
            } else {
                $nextDueDate = optional(
                    $financing->loan->paymentSchedules
                        ->whereIn('status', [
                            LoanPaymentScheduleStatus::PENDING->value,
                            LoanPaymentScheduleStatus::OVERDUE->value,
                        ])
                        ->sortBy('due_date')
                        ->first()
                )->due_date;
            }
        }

        return [
            'id' => $financing->id,
            'transaction_code' => $financing->transaction_code,
            'akad_date' => $financing->akad_date,
            'product_type' => $financing->product_type,
            'product_name' => $financing->product_name,
            'status' => $financing->status,
            'remaining_balance' => $remainingBalance,
            'loan' => $financing->loan ? [
                'tenor' => $financing->loan->tenor,
                'monthly_installment' => $financing->loan->monthly_installment,
                'remaining_margin' => $financing->loan->remaining_margin,
                'remaining_principal' => $financing->loan->remaining_principal,
                'next_due_date' => $nextDueDate,
            ] : null,
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
        $financing = Financing::with(['loan', 'loan.paymentSchedules.payment'])->where('user_id', $user->id)->findOrFail($id);
        $financing->total_price = $financing->cost_price + $financing->margin - $financing->down_payment;

        $loan = $financing->loan;
        if ($loan !== null) {
            $financing->total_paid = $loan->paymentSchedules
                ->where('status', LoanPaymentScheduleStatus::PAID->value)
                ->sum('total_amount');
            $financing->remaining_balance = $loan->remaining_margin + $loan->remaining_principal;
        } else {
            $financing->total_paid = 0;
            $financing->remaining_balance = 0;
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
