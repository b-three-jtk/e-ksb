<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\User;

class PengunduranDiriService
{
    public function getSemuaPengunduranDiri($search, $per_page, $sort_by, $sort_dir)
    {
        $query = User::whereHas('roles', function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            })
            ->whereHas('member', function ($q) {
                $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
            })
            ->when($search, function ($q) use ($search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('user_code', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });

        // Apply sorting
        $query->orderBy($sort_by, $sort_dir);

        // Paginate results
        return $query->paginate($per_page)->withQueryString();
    }

    public function getAnggotaMengundurkanDiri($id)
    {
        return User::with(['member' => function ($q) {
            $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
        }, 'member.memberDocs'])->findOrFail($id);
    }

    public function getTotalKewajiban(User $user)
    {
        return Financing::with('installment.payment')->where('member_id', $user->member->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $installment = $financing->installment;
                if (!$installment) return 0;

                $paidInstallments = $installment->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();
                $remainingInstallments = $installment->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)->count();

                // Asumsi margin flat, jadi margin per bulan tetap
                $marginPerMonth = $financing->margin_amount / $financing->tenor;
                $principalPerMonth = ($financing->cost_price - $financing->down_payment) / $financing->tenor;

                // Total kewajiban adalah sisa pokok + margin berjalan
                $sisaPokok = max(0, ($financing->cost_price - $financing->down_payment) - ($principalPerMonth * $paidInstallments));
                $marginBerjalan = $marginPerMonth * ($paidInstallments + 1); // Margin diakui sampai bulan berikutnya

                return $sisaPokok + $marginBerjalan;
            });
    }

    public function updateStatusAnggota(User $user)
    {
        $member = $user->member;
        $member->status = MemberStatusEnum::RESIGNED->value;
        $member->save();

        $user->status = UserStatusEnum::INACTIVE->value;
        $user->save();
    }
}
