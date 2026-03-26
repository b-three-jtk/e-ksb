<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\SavingTransactionDoc;
use App\Models\Account;
use App\Enums\SavingType;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Inertia\Inertia;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\Auth;

class SimpananController extends Controller
{
    /**
     * Show withdrawal information page
     */
    public function showWithdrawalInfo(Request $request)
    {
        $user = $request->user();

        // Get Simpanan Sukarela account
        $savingAccount = SavingAccount::where('user_id', $user->id)
            ->where('type', SavingType::SIMPANAN_SUKARELA->value)
            ->first();

        if (!$savingAccount) {
            return redirect()->route('user.userDashboard')
                ->with('error', 'Akun simpanan sukarela tidak ditemukan');
        }

        return inertia('User/Simpanan/Penarikan/Informasi', [
            'user' => [
                'name' => $user->name,
                'member_number' => $user->member_number,
            ],
            'savingAccount' => [
                'balance' => $savingAccount->balance,
            ],
            'withdrawalDate' => Carbon::now()->format('d F Y'),
        ]);
    }

    /**
     * Show withdrawal detail page
     */
    public function showWithdrawalDetail(Request $request)
    {
        $user = $request->user();

        // Get Simpanan Sukarela account
        $savingAccount = SavingAccount::where('user_id', $user->id)
            ->where('type', SavingType::SIMPANAN_SUKARELA->value)
            ->first();

        if (!$savingAccount) {
            return redirect()->route('user.userDashboard')
                ->with('error', 'Akun simpanan sukarela tidak ditemukan');
        }

        // Get user's saved accounts
        $savedAccounts = Account::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['account_number', 'bank_name', 'account_name']);

        // Get previous form data from session
        $previousData = session('withdrawal_form_data', []);

        return inertia('User/Simpanan/Penarikan/Detail', [
            'maxBalance' => $savingAccount->balance,
            'savedAccounts' => $savedAccounts,
            'previousData' => $previousData
        ]);
    }

    /**
     * Show withdrawal statement page
     */
    public function showWithdrawalStatement(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'method' => 'required|in:Tunai,Non-Tunai',
            'bank_name' => 'required_if:method,Non-Tunai|string|nullable',
            'account_number' => 'required_if:method,Non-Tunai|string|nullable',
            'account_name' => 'required_if:method,Non-Tunai|string|nullable',
        ]);

        // Store in session instead of query parameters
        session(['withdrawal_form_data' => $validated]);

        return inertia('User/Simpanan/Penarikan/Pernyataan', [
            'withdrawalData' => $validated,
        ]);
    }

    /**
     * Submit withdrawal transaction
     */
    public function submitWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'method' => 'required|in:Tunai,Non-Tunai',
            'bank_name' => 'required_if:method,Non-Tunai|string|nullable',
            'account_number' => 'required_if:method,Non-Tunai|string|nullable',
            'account_name' => 'required_if:method,Non-Tunai|string|nullable',
            'agreed' => 'required|boolean|accepted',
        ]);

        $user = $request->user();

        try {
            DB::beginTransaction();

            // Get Simpanan Sukarela account with lock to prevent race condition
            $savingAccount = SavingAccount::where('user_id', $user->id)
                ->where('type', SavingType::SIMPANAN_SUKARELA->value)
                ->lockForUpdate()
                ->first();

            if (!$savingAccount) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors(['error' => 'Akun simpanan sukarela tidak ditemukan']);
            }

            // Check if balance is sufficient
            if ($validated['amount'] > $savingAccount->balance) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors(['amount' => 'Saldo tidak mencukupi untuk penarikan ini']);
            }

            // Save or update account information if Non-Tunai
            if ($validated['method'] === 'Non-Tunai') {
                Account::updateOrCreate(
                    [
                        'account_number' => $validated['account_number'],
                        'user_id' => $user->id,
                    ],
                    [
                        'bank_name' => $validated['bank_name'],
                        'account_name' => $validated['account_name'],
                    ]
                );
            }

            // Create transaction
            $transaction = SavingTransaction::create([
                'id' => Str::uuid()->toString(),
                'amount' => $validated['amount'],
                'type' => 'Penarikan',
                'status' => TransactionStatus::PENDING->value,
                'method' => $validated['method'],
                'description' => $validated['description'],
                'transaction_date' => Carbon::now(),
                'updated_by' => $user->id,
                'saving_account_id' => $savingAccount->id,
            ]);

            DB::commit();

            // Clear session data
            session()->forget('withdrawal_form_data');

            return redirect()->route('user.userDashboard')
                ->with('success', 'Permohonan penarikan simpanan berhasil diajukan dan sedang dalam peninjauan');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memproses penarikan. Silakan coba lagi.']);
        }
    }

    public function createDeposit(Request $request)
    {

        $members = User::where('role_id', 9)
            ->where('status', 'Aktif')
            ->select('id', 'member_number', 'name')
            ->with(['savingAccounts:id,user_id,type,balance'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'member_number' => $user->member_number,
                    'name' => $user->name,
                    'savingAccounts' => $user->savingAccounts->map(fn($acc) => [
                        'type' => $acc->type,
                        'balance' => $acc->balance,
                    ]),
                ];
            });

        $accounts = Account::select('account_number', 'bank_name', 'account_name', 'user_id')
            ->get();

        $pengurus = Auth::user();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members'   => $members,
            'accounts'  => $accounts,
            'pengurus'  => [
                'name' => $pengurus->name ?? 'Pengurus',
            ],
        ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'member_id'        => 'required|uuid|exists:users,id',
            'saving_category'  => 'required|string|in:' . implode(',', array_column(SavingType::cases(), 'value')),
            'amount'           => 'required|numeric|min:1',
            'date'             => 'required|date|before_or_equal:today',
            'method'           => 'required|in:Tunai,Non-Tunai',
            'notes'            => 'nullable|string|max:500',
            'tenor_months' => 'nullable|integer|min:1',
            'target_amount' => 'nullable|numeric|min:1',

            // non-tunai
            'bank_name'        => 'required_if:method,Non-Tunai|string|max:100',
            'account_name'     => 'required_if:method,Non-Tunai|string|max:150',
            'account_number'   => 'required_if:method,Non-Tunai|string|max:50',
            'payment_proof'    => 'required_if:method,Non-Tunai|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $member = User::findOrFail($request->member_id);

        $savingAccount = SavingAccount::where('user_id', $member->id)
            ->where('type', $request->saving_category)
            ->first();

        if (!$savingAccount) {
            if ($request->saving_category === 'Tabungan Berjangka' && !$request->tenor_months) {
                return back()->withErrors(['tenor_months' => 'Tenor wajib diisi']);
            }

            if ($request->saving_category === 'Tabungan Ibadah' && !$request->target_amount) {
                return back()->withErrors(['target_amount' => 'Target wajib diisi']);
            }

            $savingAccount = SavingAccount::create([
                'account_number' => 'SA-' . strtoupper(Str::random(8)),
                'balance'        => 0,
                'type'           => $request->saving_category,
                'tenor_months'   => $request->tenor_months,
                'target_amount'  => $request->target_amount,
                'user_id'        => $member->id,
            ]);
        }

        $transaction = DB::transaction(function () use ($request, $savingAccount) {

            $account = null;

            if ($request->method === 'Non-Tunai') {
                $account = Account::updateOrCreate(
                    [
                        'account_number' => $request->account_number,
                        'user_id' => $request->member_id,
                    ],
                    [
                        'bank_name'    => $request->bank_name,
                        'account_name' => $request->account_name,
                    ]
                );
            }

            $transaction = SavingTransaction::create([
                'transaction_code'   => 'ST' . Str::upper(Str::random(8)),
                'amount'             => $request->amount,
                'type'               => TransactionType::DEPOSIT->value,
                'status'             => TransactionStatus::COMPLETED->value,
                'method'             => $request->method,
                'description'        => $request->notes ?? 'Penyetoran oleh pengurus',
                'transaction_date'   => $request->date,
                'updated_by'         => Auth::id(),
                'saving_account_id'  => $savingAccount->id,
                'account_number'     => $account?->account_number,
            ]);

            $savingAccount->increment('balance', $request->amount);

            if ($request->method === 'Non-Tunai' && $request->hasFile('payment_proof')) {
                $path = $request->file('payment_proof')->store(
                    'saving-transactions/' . now()->format('Y-m'),
                    'public'
                );

                SavingTransactionDoc::create([
                    'transaction_id' => $transaction->id,
                    'name'           => 'Bukti Transfer Penyetoran',
                    'attachment'     => $path,
                ]);
            }

            return $transaction;
        });

        $members = User::where('role_id', 9)
            ->where('status', 'Aktif')
            ->with('savingAccounts')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'member_number' => $user->member_number,
                    'name' => $user->name,
                    'savingAccounts' => $user->savingAccounts->map(fn($acc) => [
                        'type' => $acc->type,
                        'balance' => $acc->balance,
                    ]),
                ];
            });

        $accounts = Account::select('account_number', 'bank_name', 'account_name', 'user_id')->get();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'members'  => $members,
            'accounts' => $accounts,
            'pengurus' => [
                'name' => Auth::user()->name ?? 'Pengurus',
            ],

            'struk' => [
                'no_transaksi'  => $transaction->transaction_code,
                'tanggal'       => $transaction->created_at,
                'pengurus'      => Auth::user()->name,
                'nama_anggota'  => $member->name,
                'no_anggota'    => $member->member_number,
                'jenis'         => $savingAccount->type,
                'metode'        => $transaction->method,
                'nominal'       => $transaction->amount,
                'saldo_sebelum' => $savingAccount->balance - $transaction->amount,
                'saldo_sesudah' => $savingAccount->balance,
                'tenor'         => $savingAccount->tenor_months,
                'target'        => $savingAccount->target_amount,
            ]
        ]);
    }
}
