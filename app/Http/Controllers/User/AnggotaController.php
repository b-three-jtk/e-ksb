<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\UserDoc;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalSaving = $user->savingAccounts()->sum('balance');

        $totalInstallment = $user->financings()
            ->whereHas('loan')
            ->with('loan')
            ->get()
            ->sum(fn ($f) => $f->loan->amount_ins ?? 0);

        $ledger = SavingTransaction::whereHas(
            'savingAccount',
            fn ($q) => $q->where('user_id', $user->id)
        )
        ->with('savingAccount')
        ->latest('transaction_date')
        ->limit(5)
        ->get()
        ->map(function ($trx) {
            return [
                'date'    => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                'product' => $trx->savingAccount->type,
                'type'    => $trx->type,
                'amount'  => 'Rp ' . number_format($trx->amount, 0, ',', '.'),
            ];
        });

        $ledgerCount = SavingTransaction::whereHas(
            'savingAccount',
            fn ($q) => $q->where('user_id', $user->id)
        )
        ->whereMonth('transaction_date', now()->month)
        ->whereYear('transaction_date', now()->year)
        ->count();

        return inertia('User/Dashboard', [
            'summary' => [
                'total_saving' => $totalSaving,
                'total_installment' => $totalInstallment,
                'ledger_count' => $ledgerCount,
            ],
            'ledger' => $ledger,
        ]);
    }

    public function createResign(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $hasExistingResign = UserDoc::where('user_id', $user->id)
            ->where('name', 'Dokumen Pengunduran Diri')
            ->exists();

        $totalSaving = $user->savingAccounts()->sum('balance');

        $totalObligation = $user->financings()
            ->whereHas('loan')
            ->with(['loan.payments'])
            ->get()
            ->sum(function ($financing) {
                $loan = $financing->loan;
                if (!$loan) return 0;

                $totalPaid = $loan->payments->sum('amount');
                return max($loan->total_price - $totalPaid, 0);
            });

        return inertia('User/Resign/Create', [
            'member' => [
                'name' => $user->name,
                'member_number' => $user->member_number,
                'joined_date' => Carbon::parse($user->joined_date)->locale('id')->translatedFormat('d F Y'),
                'total_saving' => $totalSaving,
                'total_obligation' => $totalObligation,
            ],
            'has_existing_resign' => $hasExistingResign,
        ]);
    }

    public function storeResign(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $hasExistingResign = UserDoc::where('user_id', $user->id)
            ->where('name', 'Dokumen Pengunduran Diri')
            ->exists();

        if ($hasExistingResign) {
            return back()->withErrors([
                'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.',
            ]);
        }

        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ], [
            'document.max' => 'Ukuran file maksimal 2 MB',
            'document.mimes' => 'File harus berupa PDF atau DOC',
        ]);

        $path = $request->file('document')->store('resign_docs', 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'document' => 'Gagal menyimpan dokumen. Silakan coba lagi.',
            ]);
        }
        UserDoc::create([
            'name' => 'Dokumen Pengunduran Diri',
            'attachment' => $path,
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Permohonan pengunduran diri berhasil dikirim');
    }
}
