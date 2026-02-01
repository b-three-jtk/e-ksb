<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancingReqStatus;
use App\Enums\LoanStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SavingTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\UserDoc;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalSaving = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('savingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalInstallment = $user->financings()
        ->whereIn('status', [
            FinancingReqStatus::APPROVED,
            FinancingReqStatus::APPROVED_WITH_NOTES,
        ])
        ->whereHas('loan')
        ->with(['loan.payments' => fn ($q) =>
            $q->where('status', LoanStatus::PAID)
        ])
        ->get()
        ->sum(function ($financing) {
            $loan = $financing->loan;
            if (!$loan) return 0;

            $totalPaid = $loan->payments->sum('amount');

            if ($totalPaid >= $loan->total_price) {
                return 0;
            }

            return $loan->amount_ins ?? 0;
        });

        $ledger = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas(
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

        $activeMurabahahCount = $user->financings()
            ->whereIn('status', [FinancingReqStatus::APPROVED, FinancingReqStatus::APPROVED_WITH_NOTES,])
            ->whereHas('loan')
            ->with(['loan.payments' => fn ($q) =>
                $q->where('status', LoanStatus::PAID)
            ])
            ->get()
            ->filter(function ($financing) {
                $loan = $financing->loan;
                if (!$loan){
                    return false;
                }

                $totalPaid = $loan->payments->sum('amount');

                return $totalPaid < $loan->total_price;
            })
            ->count();

        return inertia('User/Dashboard', [
            'summary' => [
                'total_saving' => $totalSaving,
                'total_installment' => $totalInstallment,
                'murabahah_count' => $activeMurabahahCount,
            ],
            'ledger' => $ledger,
        ]);
    }

    public function createResign(Request $request)
    {
        $user = $request->user();

        $hasExistingResign = UserDoc::where('user_id', $user->id)
            ->where('name', 'Dokumen Pengunduran Diri')
            ->exists();

        $totalSaving = SavingTransaction::where('status', TransactionStatus::COMPLETED)
            ->whereHas('savingAccount', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalObligation = $user->financings()
            ->whereIn('status', [
                FinancingReqStatus::APPROVED, 
                FinancingReqStatus::APPROVED_WITH_NOTES,
            ])
            ->whereHas('loan')
            ->with(['loan.payments' => fn ($q) =>
                $q->where('status', LoanStatus::PAID)
            ])
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

    public function storeResign(Request $request)
    {
        $user = $request->user();

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

        return redirect()
        ->route('user.userDashboard')
        ->with('success', 'Permohonan pengunduran diri berhasil dikirim.');
    }
}
