<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Condition;
use App\Enums\Education;
use App\Enums\FinancialCost;
use App\Enums\FinancialIncome;
use App\Enums\LoanPaymentScheduleStatus;
use App\Enums\MaritalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancingRequest;
use App\Models\Financing;
use App\Enums\Heir as HeirEnum;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FinancingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $educations = array_column(Education::cases(), 'value');
        $marriageStatuses = array_column(MaritalStatus::cases(), 'value');
        $incomes = array_column(FinancialIncome::cases(), 'value');
        $expenses = array_column(FinancialCost::cases(), 'value');
        $relationships = array_column(HeirEnum::cases(), 'value');
        $conditions = array_column(Condition::cases(), 'value');

        return inertia('Admin/Financing/Create', [
            'educations' => $educations,
            'marriageStatuses' => $marriageStatuses,
            'income_types' => $incomes,
            'expense_types' => $expenses,
            'relationships' => $relationships,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFinancingRequest $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validated();

            $verifier = auth()->user();

            // data pemohon
            $user = User::with('heirs', 'financials', 'userDocs')->where('member_number', $validated['member']['member_number'])->first();
            $user->update([
                'name' => $validated['member']['name'],
                'nik' => $validated['member']['nik'],
                'gender' => $validated['member']['gender'] ?? $user->gender,
                'birth_place' => $validated['member']['birth_place'] ?? $user->birth_place,
                'birth_date' => $validated['member']['birth_date'] ?? $user->birth_date,
                'last_education' => $validated['member']['last_education'] ?? $user->last_education,
                'address' => $validated['member']['address'] ?? $user->address,
                'residential_address' => $validated['member']['residential_address'] ?? $user->residential_address,
                'email' => $validated['member']['email'] ?? $user->email,
                'marital_status' => $validated['member']['marital_status'] ?? $user->marital_status,
                'dependents' => $validated['member']['dependents'] ?? $user->dependents,
                'phone_number' => $validated['member']['phone_number'] ?? $user->phone_number,
            ]);

            // Ahli waris
            $user->heirs()->delete();
            $user->heirs()->createMany($validated['member']['heirs']);

            // Data finansial
            $user->financials()->delete();

            foreach ($validated['member']['incomes'] as $income) {
                $user->financials()->create([
                    'financial_type' => $income['financial_type'],
                    'amount' => $income['amount'],
                    'category' => true,
                ]);
            }

            foreach ($validated['member']['expenses'] as $expense) {
                $user->financials()->create([
                    'financial_type' => $expense['financial_type'],
                    'amount' => $expense['amount'],
                    'category' => false,
                ]);
            }

            // Data supplier
            $supplier = Supplier::updateOrCreate(
                ['name' => $validated['supplier']['name']],
                [
                    'address' => $validated['supplier']['address'] ?? null,
                    'contact' => $validated['supplier']['contact'] ?? null,
                    'link_address' => $validated['supplier']['link_address'] ?? null,
                ]
            );

            // Data pembiayaan
            Financing::create([
                'transaction_code' => 'TRX-' . strtoupper(uniqid()),
                'product_name' => $validated['financing']['product_name'],
                'product_type' => $validated['financing']['product_type'],
                'brand' => $validated['financing']['brand'] ?? null,
                'color' => $validated['financing']['color'] ?? null,
                'condition' => $validated['financing']['condition'] ?? null,
                'description' => $validated['financing']['description'] ?? null,
                'cost_price' => $validated['financing']['cost_price'],
                'qty' => $validated['financing']['qty'],
                'margin' => $validated['financing']['margin'] ?? 0,
                // 'tsaman_naqdy' => ,
                'down_payment' => $validated['financing']['down_payment'] ?? 0,
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'updated_by' => $verifier->id,
                'akad_date' => now(),
                'status' => FinancingReqStatus::ACTIVE_INSTALLMENTS->value,
            ]);

            DB::commit();

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation Error:', $e->errors());

            return back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing financing: ' . $e->getMessage());

            return back()
                ->withErrors(['error' => 'Gagal menyimpan pembiayaan: ' . $e->getMessage()])
                ->withInput();

        }
    }

    public function storeDraft(StoreFinancingRequest $request)
    {
        try {
            $validated = $request->validated();

            // Cari user berdasarkan member_number
            $user = User::where('member_number', $validated['member']['member_number'])->first();

            // Update user data
            $user->update([
                'gender' => $validated['member']['gender'] ?? $user->gender,
                'birth_place' => $validated['member']['birth_place'] ?? $user->birth_place,
                'birth_date' => $validated['member']['birth_date'] ?? $user->birth_date,
                'last_education' => $validated['member']['last_education'] ?? $user->last_education,
                'address' => $validated['member']['address'] ?? $user->address,
                'residential_address' => $validated['member']['residential_address'] ?? $user->residential_address,
                'marital_status' => $validated['member']['marital_status'] ?? $user->marital_status,
                'dependents' => $validated['member']['dependents'] ?? $user->dependents,
                'phone_number' => $validated['member']['phone_number'] ?? $user->phone_number,
            ]);

            // Save files if exist
            // if ($request->hasFile('member.income_slip')) {
            //     $user->addMedia($request->file('member.income_slip'))
            //         ->usingName('income_slip_' . now()->timestamp)
            //         ->toMediaCollection('financing_documents');
            // }

            // if ($request->hasFile('member.family_card')) {
            //     $user->addMedia($request->file('member.family_card'))
            //         ->usingName('family_card_' . now()->timestamp)
            //         ->toMediaCollection('financing_documents');
            // }

            // if ($request->hasFile('member.bank_book')) {
            //     $user->addMedia($request->file('member.bank_book'))
            //         ->usingName('bank_book_' . now()->timestamp)
            //         ->toMediaCollection('financing_documents');
            // }

            return redirect()->back()
                ->with('success', 'Draft berhasil disimpan');

        } catch (ValidationException $e) {
            Log::error('Validation Error:', $e->errors());

            return back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (Exception $e) {
            Log::error('Error storing draft: ' . $e->getMessage());

            return back()
                ->withErrors(['error' => 'Gagal menyimpan draft: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $financing = Financing::with(['loan', 'loan.paymentSchedules.payment'])->findOrFail($id);
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

        return inertia('Admin/Financing/Show', [
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

    public function searchSuppliers(Request $request)
    {
        $query = $request->get('q');

        $suppliers = Supplier::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return response()->json($suppliers);
    }
}
