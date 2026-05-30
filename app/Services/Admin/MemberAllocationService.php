<?php

namespace App\Services\Admin;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberAllocationService
{
    public function buildPageData(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));
        $allocationStatus = (string) $request->input('allocation_status', '');

        $memberBaseQuery = User::query()
            ->with(['member.penanggungJawab'])
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::ANGGOTA->value))
            ->whereNotNull('joined_date')
            ->whereNotNull('user_code')
            ->where('status', UserStatusEnum::ACTIVE->value);

        $query = clone $memberBaseQuery;

        if ($search !== '') {
            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('user_code', 'like', '%' . $search . '%')
                    ->orWhere('phone_number', 'like', '%' . $search . '%');
            });
        }

        if ($allocationStatus === 'allocated') {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->whereNotNull('pj_user_id'));
        } elseif ($allocationStatus === 'unallocated') {
            $query->whereHas('member', fn ($memberQuery) => $memberQuery->whereNull('pj_user_id'));
        }

        $members = $query
            ->orderByDesc('joined_date')
            ->paginate($perPage)
            ->withQueryString();

        $members->setCollection(
            $members->getCollection()->map(function (User $user) {
                $member = $user->member;

                return [
                    'id' => $user->id,
                    'member_id' => $member?->id,
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'avatar' => $user->profile_picture_url,
                    'phone_number' => $user->phone_number,
                    'joined_date' => optional($user->joined_date)->format('d M Y'),
                    'status' => $user->status,
                    'pj_id' => $member?->pj_user_id,
                    'pj_name' => $member?->penanggungJawab?->name,
                    'allocation_status' => $member?->pj_user_id ? 'Sudah Dialokasikan' : 'Belum Dialokasikan',
                ];
            })
        );

        $pjUsers = User::query()
            ->withCount('allocatedMembers')
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::PJANGGOTA->value))
            ->where('status', UserStatusEnum::ACTIVE->value)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'user_code' => $user->user_code,
                'avatar' => $user->profile_picture_url,
                'phone_number' => $user->phone_number,
                'allocated_members_count' => $user->allocated_members_count,
            ])
            ->values();

        $totalMembers = (clone $memberBaseQuery)->count();
        $allocatedMembers = (clone $memberBaseQuery)
            ->whereHas('member', fn ($memberQuery) => $memberQuery->whereNotNull('pj_user_id'))
            ->count();

        return [
            'members' => $members,
            'pjUsers' => $pjUsers,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'allocation_status' => $allocationStatus,
            ],
            'summary' => [
                'total_members' => $totalMembers,
                'allocated_members' => $allocatedMembers,
                'unallocated_members' => max($totalMembers - $allocatedMembers, 0),
            ],
        ];
    }

    public function allocate(array $validated): void
    {
        DB::transaction(function () use ($validated) {
            $pjUser = User::query()
                ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::PJANGGOTA->value))
                ->findOrFail($validated['pj_user_id']);

            Member::query()
                ->whereHas('user', fn ($userQuery) => $userQuery->where('status', UserStatusEnum::ACTIVE->value))
                ->whereIn('id', $validated['member_ids'])
                ->update([
                    'pj_user_id' => $pjUser->id,
                ]);
        });
    }
}
