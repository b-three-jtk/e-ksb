<?php
namespace App\Services\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Member;
use App\Models\Pengguna;
use App\Services\Admin\PeranAksesService;

class PengurusService
{
    public function __construct(private PeranAksesService $peranAksesService) {}
    public function getSemuaPengurus($request)
    {
        $allowedSorts = ['name', 'created_at', 'email'];
        $sortBy  = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        return Pengguna::with(['roles', 'member'])
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', [UserRoleEnum::ANGGOTA->value]);
            })
            ->whereIn('status', [
                UserStatusEnum::ACTIVE->value,
                UserStatusEnum::INACTIVE->value,
            ])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('nama', 'like', "%{$request->search}%")
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
                    $r->where('nama', $request->role)
                )
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'nik' => $user->nik,
                'nama' => $user->nama,
                'email' => $user->email,
                'posisi' => $user->getRoleNames()->first(),
                'status' => $user->member
                    ? 'Anggota'
                    : 'Non Anggota',

                'avatar' => $user->foto_profil
                    ? asset('storage/' . $user->foto_profil)
                    : null,
            ]);
    }

    public function storePengurus($data)
    {
        if (isset($data['pengguna_id']) && $data['pengguna_id']) {
            $user = Pengguna::findOrFail($data['pengguna_id']);

            $user->update([
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'no_telp' => $data['no_telp'],
            ]);

            $this->peranAksesService->syncUserRole($user, $data['role_id']);

            $user->save();
        } else {

            $user = Pengguna::create([
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'no_telp' => $data['no_telp'],
                'kode_pengguna' => 'KSP' . now()->format('ym') . str_pad(Pengguna::count() + 1, 4, '0', STR_PAD_LEFT),
                'password' => bcrypt('Password123'),
                'status' => UserStatusEnum::ACTIVE->value,
            ]);

            $this->peranAksesService->assignRoleToUser($user, $data['role_id']);
        }
    }

    public function getPengurusById($id)
    {
        $admin = Pengguna::with('roles')->findOrFail($id);

        $admin->foto_profil = $admin->foto_profil
            ? asset('storage/' . $admin->foto_profil)
            : null;

        return $admin;
    }

    public function getAnggotaAktif()
    {
        return Member::whereIn('status', [
            MemberStatusEnum::ACTIVE->value,
        ])
        ->with(['user:id,kode_pengguna,nama,nik,email,no_telp',
            'user.roles' => function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            }])
        ->get()
        ->map(function ($member) {
            return [
                'id' => $member->user->id,
                'kode_pengguna' => $member->user->kode_pengguna,
                'nama' => $member->user->nama,
                'nik' => $member->user->nik,
                'email' => $member->user->email,
                'no_telp' => $member->user->no_telp,
            ];
        });
    }

    public function updateProfil($user, array $data)
    {
        if (isset($data['foto_profile_file'])) {
                $path = $data['foto_profile_file']->store('profil', 'public');
                $data['foto_profil'] = $path;
            }

        $user->update($data);
    }
}
