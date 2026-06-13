<?php
namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Services\Admin\PeranAksesService;

class PengurusService
{
    public function __construct(private PeranAksesService $peranAksesService) {}
    public function getSemuaPengurus($request, $sortBy, $sortDir)
    {
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

    public function searchAnggotaAktif($query)
    {
        return User::with('member')
            ->whereHas('member', function ($q) {
                $q->where('status', MemberStatusEnum::ACTIVE->value);
            })
            ->whereHas('roles', function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('user_code', 'like', "%{$query}%")
                    ->orWhere('nik', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'user_code', 'name', 'nik', 'email', 'phone_number'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_code' => $user->user_code,
                    'name' => $user->name,
                    'nik' => $user->nik,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ];
            });
    }
}
