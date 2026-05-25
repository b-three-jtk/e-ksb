<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingReqStatusEnum;
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
    public function validation(string $id)
    {
        $data = [];
        $data['user'] = User::with(['member' => function ($q) {
            $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
        }, 'member.memberDocs'])->findOrFail($id);
        $data['user']->member->memberDocs->where('name', 'Dokumen Pengunduran Diri')->first();

        $resignationDoc = $data['user']->member->memberDocs?->first()?->attachment ? asset('storage/' . $data['user']->member->memberDocs->first()->attachment) : 0;

        $totalSavings = $data['user']->member->savingAccounts()->sum('balance');
        $totalObligation = Financing::with('installment.paymentSchedules.payment')->where('member_id', $data['user']->member->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($financing) {
                $installment = $financing->installment;
                if (!$installment) return 0;
                // TODO: fix calculation
                $totalUnpaid = $installment->remaining_principal + $installment->remaining_margin;

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
        $member = Member::with('user')->where('status', MemberStatusEnum::RESIGNED_REQUESTED)->where('user_id', $id)->firstOrFail();
        $member->status = MemberStatusEnum::RESIGNED->value;
        $member->user->status = UserStatusEnum::INACTIVE->value;
        $member->save();

        return to_route('admin.resignations.index')->with('success', 'Pengunduran diri berhasil divalidasi.');
    }
}
