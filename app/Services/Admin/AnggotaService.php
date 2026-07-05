<?php

namespace App\Services\Admin;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Heir;
use App\Models\Pengguna;
use App\Models\AkunSimpanan;
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

        $query = Pengguna::with('anggota.akunSimpanan')
            ->whereHas('anggota')
            ->whereNotNull('tgl_bergabung')
            ->whereNotNull('kode_pengguna');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->whereHas('anggota', function ($q) {
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
                    DB::table('akun_simpanan')
                        ->where('anggota_id', $user->anggota?->id)
                        ->sum('saldo') ?? 0,
                    0, ',', '.'
                ),
                'avatar' => $user->foto_profil
                    ? asset('storage/' . $user->foto_profil)
                    : null,
            ]);
    }

    public function getSummary(): array
    {
        $baseQuery = Pengguna::with('anggota')
            ->whereHas('anggota')
            ->whereNotNull('tgl_bergabung');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $baseQuery->whereHas('anggota', function ($q) {
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
            'anggota.memberDocs',
            'roles',
            'anggota.akunSimpanan.transactions' => fn($q) => $q->orderBy('transaction_date', 'desc'),
            'anggota.akunSimpanan',
            'anggota.heirs',
            'anggota.pembiayaan.installment.payment',
            'anggota.pembiayaan.objekPembiayaan',
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

                if ($user->anggota) {
                    $user->anggota->update([
                        'jenis_kelamin' => $validated['jenis_kelamin'] ?? $user->anggota->jenis_kelamin,
                        'tempat_lahir' => $validated['tempat_lahir'] ?? $user->anggota->tempat_lahir,
                        'tgl_lahir' => $validated['tgl_lahir'] ?? $user->anggota->tgl_lahir,
                        'alamat_ktp' => $validated['alamat_ktp'] ?? $user->anggota->alamat_ktp,
                        'alamat_domisili' => $validated['alamat_domisili'] ?? $user->anggota->alamat_domisili,
                        'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? $user->anggota->pendidikan_terakhir,
                        'status_pernikahan' => $validated['status_pernikahan'] ?? $user->anggota->status_pernikahan,
                        'jml_tanggungan' => $validated['jml_tanggungan'] ?? $user->anggota->jml_tanggungan,
                    ]);
                }

                if (!empty($validated['heirs']) && $user->anggota) {
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

                    $user->anggota->heirs()->sync($syncData);
                } elseif ($user->anggota) {
                    $user->anggota->heirs()->detach();
                }

                if (isset($validated['ktp_file']) && $user->anggota) {
                    $user->anggota->memberDocs()->updateOrCreate(
                        ['doc_name' => 'ktp', 'anggota_id' => $user->anggota->id],
                        ['doc_attachment' => $validated['ktp_file']->store('member_docs', 'public')]
                    );
                }

                if (isset($validated['kk_file']) && $user->anggota) {
                    $user->anggota->memberDocs()->updateOrCreate(
                        ['doc_name' => 'kartu_keluarga', 'anggota_id' => $user->anggota->id],
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
        return AkunSimpanan::with([
            'transactions' => fn($q) => $q->latest('transaction_date')
        ])->findOrFail($accountId);
    }

    public function getRiwayatPembiayaanAnggota($financingId)
    {
        return Pembiayaan::with([
            'installment' => fn($q) => $q->orderBy('installment_no', 'asc'),
            'installment.payment'
        ])->findOrFail($financingId);
    }
}
