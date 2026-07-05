<?php

namespace App\Services\User;

use App\Enums\MemberStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\AhliWaris;
use App\Models\Anggota;
use App\Models\DokumenAnggota;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PendaftaranAnggotaService
{
    /**
     * Register a new anggota with ahli_waris and optional documents.
     *
     * @param array<string, mixed> $validated
     * @param Request $request
     * @return array{nama: string, kode_pengguna: string, initial_password: string, no_telp: string}
     */
    public function register(array $validated, Request $request): array
    {
        $memberNumber = $this->generateMemberNumber();
        $initialPassword = Str::upper(Str::random(4)) . random_int(1000, 9999);

        DB::transaction(function () use ($validated, $request, $memberNumber, $initialPassword) {
            $user = $this->createUser($validated, $memberNumber, $initialPassword);
            $anggota = $this->createMember($validated, $user->id);

            $user->assignRole('Anggota');

            Log::info("User {$user->id} registered as anggota with user code {$memberNumber}");
            $this->createMemberAhliWaris($validated, $anggota->id);
            $this->createDokumenAnggota($request, $anggota->id);
        });

        return [
            'nama' => $validated['nama'],
            'kode_pengguna' => $memberNumber,
            'initial_password' => $initialPassword,
            'no_telp' => $validated['no_telp'],
        ];
    }

    private function generateMemberNumber(): string
    {
        $yymm = date('ym');
        $prefix = 'KSB' . $yymm;

        // Ambil suffix 3 digit terakhir saja, bukan seluruh angka
        $last = Pengguna::query()
            ->where('kode_pengguna', 'like', $prefix . '%')
            ->orderBy('kode_pengguna', 'desc')
            ->lockForUpdate() // ← cegah race kondisi_produk
            ->value('kode_pengguna');

        $lastSequence = $last ? (int) substr($last, -3) : 0;
        $nextSequence = $lastSequence + 1;

        return $prefix . str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $validated
     * @param string $memberNumber
     * @param string $initialPassword
     * @return Pengguna
     */
    private function createUser(array $validated, string $memberNumber, string $initialPassword): Pengguna
    {
        $email = $validated['email'] ?? null;

        return Pengguna::create([
            'kode_pengguna' => $memberNumber,
            'nama' => $validated['nama'],
            'nik' => $validated['nik'],
            'no_telp' => $validated['no_telp'],
            'email' => $email,
            'status' => UserStatusEnum::ACTIVE->value,
            'tgl_bergabung' => now()->toDateString(),
            'password' => Hash::make($initialPassword),
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @param string $userId
     * @return Anggota
     */
    private function createMember(array $validated, string $userId): Anggota
    {
        return Anggota::create([
            'pengguna_id' => $userId,
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'tempat_lahir' => $validated['tempat_lahir'],
            'tgl_lahir' => $validated['tgl_lahir'],
            'status_pernikahan' => $validated['status_pernikahan'],
            'alamat_domisili' => $validated['alamat_domisili'],
            'alamat_ktp' => $validated['alamat_ktp'] ?? null,
            'pendidikan_terakhir' => $validated['pendidikan_terakhir'],
            'status' => MemberStatusEnum::PAYMENT_PENDING->value,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @param string $anggotaId
     * @return void
     */
    private function createMemberAhliWaris(array $validated, string $anggotaId): void
    {
        $ahli_waris = AhliWaris::firstOrCreate(
            ['nik_ahli_waris' => $validated['nik_ahli_waris']],
            [
                'nama_ahli_waris' => $validated['nama_ahli_waris'],
                'kontak_ahli_waris' => $validated['kontak_ahli_waris'],
            ]
        );

        Anggota::find($anggotaId)->ahliWaris()->syncWithoutDetaching([
            $ahli_waris->nik_ahli_waris => ['hubungan' => $validated['heir_hubungan']]
        ]);
    }

    /**
     * @param Request $request
     * @param string $anggotaId
     * @return void
     */
    private function createDokumenAnggota(Request $request, string $anggotaId): void
    {
        if ($request->hasFile('ktp_photo')) {
            $ktpPath = $request->file('ktp_photo')->store('documents', 'public');
            DokumenAnggota::create([
                'nama_dokumen' => 'ktp',
                'lampiran_dokumen' => $ktpPath,
                'anggota_id' => $anggotaId,
            ]);
        }

        if ($request->hasFile('kk_photo')) {
            $kkPath = $request->file('kk_photo')->store('documents', 'public');
            DokumenAnggota::create([
                'nama_dokumen' => 'kartu_keluarga',
                'lampiran_dokumen' => $kkPath,
                'anggota_id' => $anggotaId,
            ]);
        }
    }
}
