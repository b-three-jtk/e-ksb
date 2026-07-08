<?php

namespace App\Services\User;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Anggota;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlokasiAnggotaService
{
    /**
     * Build the pagination and allocation list data for mapping view.
     *
     * @param Request $request
     * @return array
     */
    public function buildPageData(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 10);
        $search = trim((string) $request->input('search', ''));
        $allocationStatus = (string) $request->input('allocation_status', '');

        $memberBaseQuery = Pengguna::query()
            ->with(['anggota.penanggungJawab'])
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::ANGGOTA->value))
            ->whereNotNull('tgl_bergabung')
            ->whereNotNull('kode_pengguna')
            ->where('status', UserStatusEnum::ACTIVE->value);

        $query = clone $memberBaseQuery;

        if ($search !== '') {
            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('kode_pengguna', 'like', '%' . $search . '%')
                    ->orWhere('no_telp', 'like', '%' . $search . '%');
            });
        }

        if ($allocationStatus === 'allocated') {
            $query->whereHas('anggota', fn ($memberQuery) => $memberQuery->whereNotNull('pj_anggota_id'));
        } elseif ($allocationStatus === 'unallocated') {
            $query->whereHas('anggota', fn ($memberQuery) => $memberQuery->whereNull('pj_anggota_id'));
        }

        $anggota = $query
            ->orderByDesc('tgl_bergabung')
            ->paginate($perPage)
            ->withQueryString();

        $anggota->setCollection(
            $anggota->getCollection()->map(function (Pengguna $user) {
                $anggota = $user->anggota;

                return [
                    'id' => $user->id,
                    'anggota_id' => $anggota?->id,
                    'kode_pengguna' => $user->kode_pengguna,
                    'nama' => $user->nama,
                    'avatar' => $user->foto_profile_url,
                    'no_telp' => $user->no_telp,
                    'tgl_bergabung' => optional($user->tgl_bergabung)->format('d M Y'),
                    'status' => $user->status,
                    'pj_id' => $anggota?->pj_anggota_id,
                    'pj_name' => $anggota?->penanggungJawab?->nama,
                    'allocation_status' => $anggota?->pj_anggota_id ? 'Sudah Dialokasikan' : 'Belum Dialokasikan',
                ];
            })
        );

        $pjUsers = Pengguna::query()
            ->withCount('allocatedMembers')
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::PJANGGOTA->value))
            ->where('status', UserStatusEnum::ACTIVE->value)
            ->orderBy('nama')
            ->get()
            ->map(fn (Pengguna $user) => [
                'id' => $user->id,
                'nama' => $user->nama,
                'kode_pengguna' => $user->kode_pengguna,
                'avatar' => $user->foto_profile_url,
                'no_telp' => $user->no_telp,
                'allocated_members_count' => $user->allocated_members_count,
            ])
            ->values();

        $totalMembers = (clone $memberBaseQuery)->count();
        $allocatedMembers = (clone $memberBaseQuery)
            ->whereHas('anggota', fn ($memberQuery) => $memberQuery->whereNotNull('pj_anggota_id'))
            ->count();

        return [
            'anggota' => $anggota,
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

    /**
     * Allocate anggota to a specific PJ.
     *
     * @param array{pj_anggota_id: mixed, member_ids: array} $validated
     * @return void
     */
    public function allocate(array $validated): void
    {
        DB::transaction(function () use ($validated) {
            $pjUser = Pengguna::query()
                ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRoleEnum::PJANGGOTA->value))
                ->findOrFail($validated['pj_anggota_id']);

            Anggota::query()
                ->whereHas('user', fn ($userQuery) => $userQuery->where('status', UserStatusEnum::ACTIVE->value))
                ->whereIn('id', $validated['member_ids'])
                ->update([
                    'pj_anggota_id' => $pjUser->id,
                ]);
        });
    }
}
