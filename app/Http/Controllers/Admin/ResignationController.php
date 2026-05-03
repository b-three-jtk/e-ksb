<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Financing;
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

        $query = User::whereHas('role', function ($q) {
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
        $data['user'] = User::with('userDocs', 'savingAccounts')->where('status', UserStatusEnum::RESIGNED_REQUESTED)->findOrFail($id);
        $data['user']->userDocs->where('name', 'Dokumen Pengunduran Diri')->first();

        $resignationDoc = $data['user']->userDocs?->first()?->attachment ? asset('storage/' . $data['user']->userDocs->first()->attachment) : 0;

        $totalSavings = $data['user']->savingAccounts()->sum('balance');
        $totalObligation = Financing::with('installment.paymentSchedules.payment')->where('user_id', $data['user']->id)
            ->where('financing_status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
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

    public function validate(Request $request, string $id)
    {
        $user = User::where('status', UserStatusEnum::RESIGNED_REQUESTED)->findOrFail($id);
        $request->status === 'reject' ? $user->status = UserStatusEnum::RESIGNED_REJECTED : $user->status = UserStatusEnum::INACTIVE;
        $user->save();

        return to_route('admin.resignations.index')->with('success', 'Pengunduran diri berhasil divalidasi.');
    }
}
