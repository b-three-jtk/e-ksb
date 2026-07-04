<?php

namespace App\Services\Admin;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Financing;
use App\Models\Heir;
use App\Models\Pengguna;
use App\Models\SavingAccount;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnggotaService
{
    public function getListAnggota(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $allowedSorts = ['nama', 'tgl_bergabung'];
        $sortBy  = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'tgl_bergabung';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $query = Pengguna::with('member.savingAccounts')
            ->whereHas('member')
            ->whereNotNull('tgl_bergabung')
            ->whereNotNull('kode_pengguna');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('member', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            });
        }

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('nama', 'ILIKE', "%{$request->search}%")
                    ->orWhere('kode_pengguna', 'ILIKE', "%{$request->search}%")
                    ->orWhere('no_telp', 'ILIKE', "%{$request->search}%")
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
                'no_anggota'    => $user->kode_pengguna,
                'nama'          => $user->nama,
                'joined_at'     => $user->tgl_bergabung
                    ? \Carbon\Carbon::parse($user->tgl_bergabung)->format('d/m/Y')
                    : null,
                'phone'         => $user->no_telp,
                'status'        => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    DB::table('saving_accounts')
                        ->where('member_id', $user->member?->id)
                        ->sum('balance') ?? 0,
                    0, ',', '.'
                ),
                'avatar' => $user->foto_profil
                    ? asset('storage/' . $user->foto_profil)
                    : null,
            ]);
    }

    public function getSummary(): array
    {
        $baseQuery = Pengguna::with('member')
            ->whereHas('member')
            ->whereNotNull('tgl_bergabung');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $baseQuery->whereHas('member', function ($q) {
                $q->where('pj_anggota_id', auth()->id());
            });
        }

        $total          = (clone $baseQuery)->count();
        $active         = (clone $baseQuery)->where('status', UserStatusEnum::ACTIVE)->count();
        $newThisMonth   = (clone $baseQuery)
            ->whereMonth('tgl_bergabung', now()->month)
            ->whereYear('tgl_bergabung', now()->year)
            ->count();

        return [
            'total_verified'  => $total,
            'active'          => $active,
            'new_this_month'  => $newThisMonth,
            'active_percent'  => $total > 0 ? round(($active / $total) * 100) : 0,
            'new_percent'     => $total > 0 ? round(($newThisMonth / $total) * 100) : 0,
        ];
    }

    public function getDetailAnggota(string $id)
    {
        $user = Pengguna::with([
            'member.memberDocs',
            'roles',
            'member.savingAccounts.transactions' => fn($q) => $q->orderBy('transaction_date', 'desc'),
            'member.savingAccounts',
            'member.heirs',
            'member.financings.installment.payment',
            'member.financings.financingItem',
        ])->findOrFail($id);

        $user->foto_profil = $user->foto_profil ? asset('storage/' . $user->foto_profil) : null;
        return $user;
    }

    public function updateMemberData(Pengguna $user, array $validated): void
    {
        try {
            DB::transaction(function () use ($user, $validated) {



                $user->update([
                    'nama' => $validated['nama'] ?? $user->nama,
                    'nik' => $validated['nik'] ?? $user->nik,
                    'email' => $validated['email'] ?? $user->email,
                    'no_telp' => $validated['no_telp'] ?? $user->no_telp,
                ]);

                if ($user->member) {
                    $user->member->update([
                        'gender' => $validated['gender'] ?? $user->member->gender,
                        'birth_place' => $validated['birth_place'] ?? $user->member->birth_place,
                        'birth_date' => $validated['birth_date'] ?? $user->member->birth_date,
                        'residential_address' => $validated['residential_address'] ?? $user->member->residential_address,
                        'domicile_address' => $validated['domicile_address'] ?? $user->member->domicile_address,
                        'last_education' => $validated['last_education'] ?? $user->member->last_education,
                        'marital_status' => $validated['marital_status'] ?? $user->member->marital_status,
                        'dependents' => $validated['dependents'] ?? $user->member->dependents,
                    ]);
                }

                if (!empty($validated['heirs']) && $user->member) {
                    $syncData = [];

                    foreach ($validated['heirs'] as $heirInput) {
                        $heir = Heir::firstOrCreate(
                            ['heir_nik' => $heirInput['heir_nik']],
                            [
                                'heir_name' => $heirInput['heir_name'],
                                'heir_contact' => $heirInput['heir_contact'] ?? null,
                            ]
                        );

                        $syncData[$heir->heir_nik] = ['relationship' => $heirInput['relationship']];
                    }

                    $user->member->heirs()->sync($syncData);
                } elseif ($user->member) {
                    $user->member->heirs()->detach();
                }

                if (isset($validated['ktp_file']) && $user->member) {
                    $user->member->memberDocs()->updateOrCreate(
                        ['doc_name' => 'ktp', 'member_id' => $user->member->id],
                        ['doc_attachment' => $validated['ktp_file']->store('member_docs', 'public')]
                    );
                }

                if (isset($validated['kk_file']) && $user->member) {
                    $user->member->memberDocs()->updateOrCreate(
                        ['doc_name' => 'kartu_keluarga', 'member_id' => $user->member->id],
                        ['doc_attachment' => $validated['kk_file']->store('member_docs', 'public')]
                    );
                }
            });
        } catch (Exception $e) {
            Log::info('error'. $e->getMessage());
            throw $e;
        }
    }

    public function getMutasiSimpananAnggota($accountId)
    {
        return SavingAccount::with([
            'transactions' => fn($q) => $q->latest('transaction_date')
        ])->findOrFail($accountId);
    }

    public function getRiwayatPembiayaanAnggota($financingId)
    {
        return Financing::with([
            'installment' => fn($q) => $q->orderBy('installment_no', 'asc'),
            'installment.payment'
        ])->findOrFail($financingId);
    }
}
