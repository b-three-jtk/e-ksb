<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;

class ResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $per_page = $request->input('per_page', 10);
        $sort_by = $request->input('sort_by', 'created_at');
        $sort_dir = $request->input('sort_dir', 'desc');

        $query = User::whereHas('roles', function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            })
            ->whereHas('member', function ($q) {
                $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
            })
            ->when($search, function ($q) use ($search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('user_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });

        // Apply sorting
        $query->orderBy($sort_by, $sort_dir);

        // Paginate results
        $members = $query->paginate($per_page)->withQueryString();

        return inertia('Admin/User/Resignation/List', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort_dir' => $sort_dir,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function validation($id)
    {
        $data = [];
        $data['user'] = User::with(['member' => function ($q) {
            $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
        }, 'member.memberDocs'])->findOrFail($id);

        $resignationDoc = $data['user']->member->memberDocs?->first()?->doc_attachment ? asset('storage/' . $data['user']->member->memberDocs->first()->doc_attachment) : null;

        $totalSavings = $data['user']->member->savingAccounts()->sum('balance');
        $totalObligation = Financing::with('installment.payment')->where('member_id', $data['user']->member->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $installment = $financing->installment;
                if (!$installment) return 0;
                $totalPrice = $financing->cost_price + $financing->margin - $financing->down_payment;
                $totalUnpaid = $totalPrice - $installment->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)->sum('amount');

                return $totalUnpaid;
        });

        return inertia('Admin/User/Resignation/Validation', [
            'data' => [
                ...$data['user']->toArray(),
                'resignation_doc' => $resignationDoc,
                'total_savings' => $totalSavings,
                'total_obligations' => $totalObligation,
            ]
        ]);
    }

    public function validate(string $id)
    {
        $member = Member::with('user')
            ->where('status', MemberStatusEnum::RESIGNED_REQUESTED)
            ->where('user_id', $id)
            ->firstOrFail();

        $member->status = MemberStatusEnum::RESIGNED->value;
        $member->save();

        $member->user->status = UserStatusEnum::INACTIVE->value;
        $member->user->save();

        return to_route('admin.resignations.index')->with([
            'success' => 'Pengunduran diri berhasil divalidasi.',
            'resignation_info' => [
                'name'      => $member->user->name,
                'user_code' => $member->user->user_code,
                'phone'     => $member->user->phone,
            ],
        ]);
    }
}
