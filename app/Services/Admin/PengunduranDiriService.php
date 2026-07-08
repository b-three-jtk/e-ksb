<?php
namespace App\Services\Admin;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Pembiayaan;
use App\Models\Pengguna;

class PengunduranDiriService
{
    public function getSemuaPengunduranDiri($search, $per_page, $sort_by, $sort_dir)
    {
        $query = Pengguna::whereHas('roles', function ($q) {
                $q->where('name', UserRoleEnum::ANGGOTA->value);
            })
            ->whereHas('anggota', function ($q) {
                $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
            })
            ->when($search, function ($q) use ($search) {
                return $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode_pengguna', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%");
            });

        // Apply sorting
        $query->orderBy($sort_by, $sort_dir);

        // Paginate results
        return $query->paginate($per_page)->withQueryString();
    }

    public function getAnggotaMengundurkanDiri($id)
    {
        return Pengguna::with(['anggota' => function ($q) {
            $q->where('status', MemberStatusEnum::RESIGNED_REQUESTED->value);
        }, 'anggota.dokumenAnggota'])->findOrFail($id);
    }

    public function getTotalKewajiban(Pengguna $user)
    {
        return Pembiayaan::with('angsuran.payment')->where('anggota_id', $user->anggota->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->get()
            ->sum(function ($accountpembiayaan) {
                $angsuran = $accountpembiayaan->angsuran;
                if (!$angsuran) return 0;

                $paidInstallments = $angsuran->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();
                $remainingInstallments = $angsuran->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)->count();

                // Asumsi margin flat, jadi margin per bulan tetap
                $marginPerMonth = $accountpembiayaan->margin_keuntungan / $accountpembiayaan->tenor;
                $principalPerMonth = ($accountpembiayaan->harga_perolehan - $accountpembiayaan->uang_muka) / $accountpembiayaan->tenor;

                // Total kewajiban adalah sisa pokok + margin berjalan
                $sisaPokok = max(0, ($accountpembiayaan->harga_perolehan - $accountpembiayaan->uang_muka) - ($principalPerMonth * $paidInstallments));
                $marginBerjalan = $marginPerMonth * ($paidInstallments + 1); // Margin diakui sampai bulan berikutnya

                return $sisaPokok + $marginBerjalan;
            });
    }

    public function updateStatusAnggota(Pengguna $user)
    {
        $anggota = $user->anggota;
        $anggota->status = MemberStatusEnum::RESIGNED->value;
        $anggota->tgl_pengunduran_diri = now();
        $anggota->save();

        $user->status = UserStatusEnum::INACTIVE->value;
        $user->save();
    }
}
