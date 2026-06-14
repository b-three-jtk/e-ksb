<?php

namespace App\Services\Admin;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnggotaService
{
    public function getListAnggota(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $allowedSorts = ['name', 'joined_date'];
        $sortBy  = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'joined_date';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query = User::with('member.savingAccounts')
            ->whereHas('member')
            ->whereNotNull('joined_date')
            ->whereNotNull('user_code');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('member', function ($q) {
                $q->where('pj_user_id', auth()->id());
            });
        }

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'ILIKE', "%{$request->search}%")
                    ->orWhere('user_code', 'ILIKE', "%{$request->search}%")
                    ->orWhere('phone_number', 'ILIKE', "%{$request->search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->input('per_page', 10))
            ->withQueryString()
            ->through(fn($user) => [
                'id'            => $user->id,
                'no_anggota'    => $user->user_code,
                'name'          => $user->name,
                'joined_at'     => $user->joined_date
                    ? \Carbon\Carbon::parse($user->joined_date)->format('d/m/Y')
                    : null,
                'phone'         => $user->phone_number,
                'status'        => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    DB::table('saving_accounts')
                        ->where('member_id', $user->member?->id)
                        ->sum('balance') ?? 0,
                    0, ',', '.'
                ),
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);
    }

    public function getSummary(): array
    {
        $baseQuery = User::with('member')
            ->whereHas('member')
            ->whereNotNull('joined_date');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $baseQuery->whereHas('member', function ($q) {
                $q->where('pj_user_id', auth()->id());
            });
        }

        $total          = (clone $baseQuery)->count();
        $active         = (clone $baseQuery)->where('status', UserStatusEnum::ACTIVE)->count();
        $newThisMonth   = (clone $baseQuery)
            ->whereMonth('joined_date', now()->month)
            ->whereYear('joined_date', now()->year)
            ->count();

        return [
            'total_verified'  => $total,
            'active'          => $active,
            'new_this_month'  => $newThisMonth,
            'active_percent'  => $total > 0 ? round(($active / $total) * 100) : 0,
            'new_percent'     => $total > 0 ? round(($newThisMonth / $total) * 100) : 0,
        ];
    }
}