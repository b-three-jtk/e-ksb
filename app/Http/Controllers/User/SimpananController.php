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
