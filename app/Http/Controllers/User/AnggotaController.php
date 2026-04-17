<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateResignRequest;
use App\Models\Financing;
use App\Models\SavingTransaction;
use App\Models\UserDoc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $totalSaving = SavingTransaction::whereHas(
                'savingAccount',
                fn($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN transaction_type = 'Penyetoran' THEN amount
                    WHEN transaction_type = 'Penarikan' THEN -amount
                END
            "));

        $totalInstallment = Financing::with('installment')->where('user_id', $user->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $installment = $financing->installment;
                if (!$installment)
                    return 0;

                $totalUnpaid = $installment->remaining_principal + $installment->remaining_margin;

                return $totalUnpaid;
            });

        $ledger = SavingTransaction::whereHas(
                'savingAccount',
                fn($q) => $q->where('user_id', $user->id)
            )
            ->with('savingAccount')
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'date' => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'product' => $trx->savingAccount->type,
                    'type' => $trx->type,
                    'amount' => 'Rp ' . number_format($trx->amount, 0, ',', '.'),
                ];
            });

        $activeMurabahahCount = Financing::where('user_id', $user->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
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

    public function createResign()
    {
        $user = auth()->user();

        $hasExistingResign = $user->status === UserStatusEnum::RESIGNED_REQUESTED->value;

        $totalSaving = SavingTransaction::whereHas(
                'savingAccount',
                fn($q) =>
                $q->where('user_id', $user->id)
            )
            ->sum(DB::raw("
                CASE
                    WHEN type = 'Penyetoran' THEN amount
                    WHEN type = 'Penarikan' THEN -amount
                END
            "));

        $totalObligation = Financing::with('installment')->where('user_id', $user->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $installment = $financing->installment;
                if (!$installment)
                    return 0;

                $totalUnpaid = $installment->remaining_principal + $installment->remaining_margin;

                return $totalUnpaid;
            });

        return inertia('User/Resign/Create', [
            'member' => [
                ...$user->toArray(),
                'total_saving' => $totalSaving,
                'total_obligation' => $totalObligation,
            ],
            'has_existing_resign' => $hasExistingResign,
        ]);
    }

    public function storeResign(CreateResignRequest $request)
    {
        $user = auth()->user();

        $hasExistingResign = $user->status === UserStatusEnum::RESIGNED_REQUESTED->value;

        if ($hasExistingResign) {
            return back()->withErrors([
                'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.',
            ]);
        }

        $data = $request->validated();

        $path = $data['document']->store('resign_docs', 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'document' => 'Gagal menyimpan dokumen. Silakan coba lagi.',
            ]);
        }

        DB::beginTransaction();
        try {
            UserDoc::create([
                'name' => 'Dokumen Pengunduran Diri',
                'attachment' => $path,
                'user_id' => $user->id,
            ]);
            $user->status = UserStatusEnum::RESIGNED_REQUESTED->value;
            $user->save();

            DB::commit();

            return redirect()
                ->route('user.userDashboard')
                ->with('success', 'Permohonan pengunduran diri berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'resign' => 'Terjadi kesalahan saat mengajukan pengunduran diri. Silakan coba lagi.',
            ]);
        }
    }
}
