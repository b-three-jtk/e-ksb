<?php

namespace App\Services;

use App\Models\Poin;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfilPenggunaService
{
    /**
     * Build profile payload data including point histories and document paths.
     *
     * @param Pengguna $user
     * @return array
     */
    public function index(Pengguna $user): array
    {
        $anggota = $user->anggota?->loadMissing(['ahliWaris', 'memberDocs']);
        $poin = $user->poin()
            ->with('transaksiSimpanan')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $getSnapshotValue = function (Poin $transaction): float {
            return (float) ($transaction->sisa_tabungan_snapshot
                ?? $transaction->transaksiSimpanan?->saldo_setelah_transaksi
                ?? 0);
        };

        $runningPointTotal = 0;
        $pointHistory = $poin
            ->map(function (Poin $transaction) use (&$runningPointTotal, $getSnapshotValue) {
                $runningPointTotal += (int) $transaction->jml_perolehan;

                return [
                    'id' => $transaction->id,
                    'calculation_date' => $transaction->periode_kalkulasi
                        ? Carbon::parse($transaction->periode_kalkulasi)->translatedFormat('d/m/Y')
                        : Carbon::parse($transaction->created_at)->format('d/m/Y'),
                    'total_simpanan' => $getSnapshotValue($transaction),
                    'points_earned' => (int) $transaction->jml_perolehan,
                    'total_points' => $runningPointTotal,
                    'deskripsi' => $transaction->deskripsi,
                ];
            })
            ->reverse()
            ->values();

        $latestPointTransaction = $poin->last();

        $photoUrl = $user->foto_profil ? asset('storage/' . $user->foto_profil) : null;
        $ahli_waris = $anggota?->ahliWaris?->map(function ($ahli_waris) {
            return [
                'nik_ahli_waris' => $ahli_waris->nik_ahli_waris,
                'nama_ahli_waris' => $ahli_waris->nama_ahli_waris,
                'hubungan' => $ahli_waris->hubungan,
                'kontak_ahli_waris' => $ahli_waris->kontak_ahli_waris,
            ];
        })->values() ?? collect();

        $spouseAhliWaris = $ahli_waris->first(function ($ahli_waris) {
            return in_array($ahli_waris['hubungan'] ?? '', ['Suami', 'Istri'], true);
        });

        $ktpDocument = $anggota?->memberDocs?->firstWhere('doc_name', 'ktp');
        $kkDocument = $anggota?->memberDocs?->firstWhere('doc_name', 'kartu_keluarga');

        return [
            'id' => $user->id,
            'kode_pengguna' => $user->kode_pengguna,
            'nama' => $user->nama,
            'nik' => $user->nik,
            'email' => $user->email,
            'no_telp' => $user->no_telp,
            'foto_profil' => $user->foto_profil,
            'photo_url' => $photoUrl,
            'role_name' => $user->getRoleNames()->first() ?? 'Anggota',
            'anggota'=> [
                'jenis_kelamin' => $anggota?->jenis_kelamin,
                'tempat_lahir' => $anggota?->tempat_lahir,
                'tgl_lahir' => $anggota?->tgl_lahir
                    ? Carbon::parse($anggota->tgl_lahir)->translatedFormat('d M Y')
                    : null,
                'status' => $anggota?->status,
                'alamat_domisili' => $anggota?->alamat_domisili,
                'alamat_ktp' => $anggota?->alamat_ktp,
                'status_pernikahan' => $anggota?->status_pernikahan,
                'pendidikan_terakhir' => $anggota?->pendidikan_terakhir,
                'jml_tanggungan' => $anggota?->jml_tanggungan,
                'spouse_name' => $anggota?->spouse_name ?? $spouseAhliWaris['nama_ahli_waris'] ?? null,
                'ahli_waris' => $ahli_waris,
                'documents' => [
                    'ktp' => $ktpDocument?->doc_attachment ? asset('storage/' . $ktpDocument->doc_attachment) : null,
                    'kk' => $kkDocument?->doc_attachment ? asset('storage/' . $kkDocument->doc_attachment) : null,
                ],
            ],
            'points' => [
                'summary' => [
                    'total_points' => (int) $poin->sum('jml_perolehan'),
                    'latest_points_earned' => (int) ($latestPointTransaction?->jml_perolehan ?? 0),
                    'latest_calculated_at' => $latestPointTransaction?->periode_kalkulasi
                        ? Carbon::parse($latestPointTransaction->periode_kalkulasi)->translatedFormat('d/m/Y')
                        : ($latestPointTransaction?->created_at
                            ? Carbon::parse($latestPointTransaction->created_at)->format('d/m/Y')
                            : null),
                    'latest_total_simpanan' => $latestPointTransaction
                        ? $getSnapshotValue($latestPointTransaction)
                        : 0,
                ],
                'history' => $pointHistory,
            ],
        ];
    }

    /**
     * Update basic user and anggota profile details.
     *
     * @param Pengguna $user
     * @param array $validated
     * @return void
     */
    public function update(Pengguna $user, array $validated): void
    {
        $user->update([
            'nama' => $validated['nama'],
            'email' => $validated['email'] ?? null,
            'no_telp' => $validated['no_telp'] ?? null,
        ]);

        if ($user->anggota) {
            $user->anggota->update([
                'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? null,
                'alamat_ktp' => $validated['alamat_ktp'] ?? null,
            ]);
        }
    }

    /**
     * Update/upload user profile avatar picture.
     *
     * @param Pengguna $user
     * @param UploadedFile $profilePicture
     * @return void
     */
    public function updateAvatar(Pengguna $user, UploadedFile $profilePicture): void
    {
        if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        $path = $profilePicture->store('profil', 'public');

        $user->update([
            'foto_profil' => $path,
        ]);
    }

    /**
     * Delete user profile avatar picture.
     *
     * @param Pengguna $user
     * @return void
     */
    public function deleteAvatar(Pengguna $user): void
    {
        if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        $user->update([
            'foto_profil' => null,
        ]);
    }

    /**
     * Update user password.
     *
     * @param Pengguna $user
     * @param string $password
     * @return void
     */
    public function updatePassword(Pengguna $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
