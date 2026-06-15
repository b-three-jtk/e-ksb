<?php
namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\User;
use App\Services\Admin\PeranAksesService;

class PengurusService
{
    public function __construct(private PeranAksesService $peranAksesService) {}
    public function getSemuaPengurus($request)
    {
        $allowedSorts = ['name', 'created_at', 'email'];
        $sortBy  = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        return User::with(['roles', 'member'])
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', [UserRoleEnum::ANGGOTA->value]);
            })
            ->whereIn('status', [
                UserStatusEnum::ACTIVE->value,
                UserStatusEnum::INACTIVE->value,
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('name', 'like', "%{$request->search}%")
                        ->orWhere('nik', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status === 'Anggota', function ($q) {
                $q->whereHas('member');
            })
            ->when($request->status === 'Non Anggota', function ($q) {
                $q->whereDoesntHave('member');
            })
            ->when(
                $request->role,
                fn($q) =>
                $q->whereHas(
                    'roles',
                    fn($r) =>
                    $r->where('name', $request->role)
                )
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'nik' => $user->nik,
                'name' => $user->name,
                'email' => $user->email,
                'posisi' => $user->getRoleNames()->first(),
                'status' => $user->member
                    ? 'Anggota'
                    : 'Non Anggota',

                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);
    }

    public function storePengurus($data)
    {
        if (isset($data['user_id']) && $data['user_id']) {
            $user = User::findOrFail($data['user_id']);

            $user->update([
                'name' => $data['name'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
            ]);

            $this->peranAksesService->syncUserRole($user, $data['role_id']);

            $user->save();
        } else {

            $user = User::create([
                'name' => $data['name'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'user_code' => 'KSP' . now()->format('Ym') . str_pad(User::count() + 1, 4, '0', STR_PAD_LEFT),
                'password' => bcrypt('Password123'),
                'status' => UserStatusEnum::ACTIVE->value,
            ]);

            $this->peranAksesService->assignRoleToUser($user, $data['role_id']);
        }
    }

    public function getPengurusById($id)
    {
        $admin = User::with('roles')->findOrFail($id);

        $admin->profile_picture = $admin->profile_picture
            ? asset('storage/' . $admin->profile_picture)
            : null;

        return $admin;
    }

    public function getAnggotaAktif()
    {
        return Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
        ])
        ->with(['user:id,user_code,name,nik,email,phone_number',
            'user.roles' => function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            }])
        ->get()
        ->map(function ($member) {
            return [
                'id' => $member->user->id,
                'user_code' => $member->user->user_code,
                'name' => $member->user->name,
                'nik' => $member->user->nik,
                'email' => $member->user->email,
                'phone_number' => $member->user->phone_number,
            ];
        });
    }
}
