<?php
namespace App\Services\Admin;

use App\Enums\ConditionEnum;
use App\Enums\EducationEnum;
use App\Enums\KeuanganAnggotaCostEnum;
use App\Enums\KeuanganAnggotaIncomeEnum;
use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\AhliWarisEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PositionEnum;
use App\Models\KeuanganAnggota;
use App\Models\Pembiayaan;
use App\Models\ObjekPembiayaan;
use App\Models\PengaturanUmum;
use App\Models\AhliWaris;
use App\Models\Angsuran;
use App\Models\DetailJurnal;
use App\Models\Anggota;
use App\Models\Pemasok;
use App\Models\Pengguna;
use App\Models\Wakalah;
use App\Services\PembiayaanService as SharedPembiayaanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembiayaanService
{
    public function __construct(private SharedPembiayaanService $sharedPembiayaanService)
    {
    }

    public function getSemuaPembiayaan($search, $tab, $verifier)
    {
        return Pembiayaan::with([
            'anggota.user' => function ($query) {
                $query->select('id', 'nama', 'kode_pengguna');
            },
            'angsuran',
            'objekPembiayaan.jenisBarang' => function ($query) {
                $query->select('jenis_barang.id', 'jenis_barang.nama_jenis_barang');
            }
        ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('anggota.user', function ($userQuery) use ($search) {
                    $userQuery->where(function ($userSearchQuery) use ($search) {
                        $userSearchQuery->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode_pengguna', 'like', "%{$search}%");
                    });
                });
            })
            ->when($tab === 'request', function ($q) use ($verifier) {
                if (in_array($verifier->getRoleNames()->first(), ['Ketua Murabahah'])) {
                    $q->where(
                        'status',
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                    );
                } else if (in_array($verifier->getRoleNames()->first(), ['Staf Murabahah'])) {
                    $q->whereIn('status', [
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    ]);
                } else {
                    $q->whereIn('status', [
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                    ]);
                }
            })
            ->when($tab === 'validated', function ($q) {
                $q->whereIn('status', [
                    FinancingReqStatusEnum::APPROVED->value,
                    FinancingReqStatusEnum::REJECTED->value,
                    FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
                ]);
            })
            ->when($tab === 'active', function ($q) {
                $q->where(
                    'status',
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                );
            })->latest('updated_at');
    }

    public function getTotalPermohonanPembiayaan()
    {
        return Pembiayaan::whereIn('status', [
            FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            FinancingReqStatusEnum::PENDING_REVIEW->value,
            FinancingReqStatusEnum::APPROVED->value,
            FinancingReqStatusEnum::REJECTED->value,
            FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
        ])->count();
    }

    public function getModalBelumDiputar()
    {
        $modalCredit = DetailJurnal::whereHas(
            'akun',
            function ($q) {
                $q->where('nama_akun', 'Dana Alokasi Pembiayaan Murabahah');
            }
        )
        ->where('posisi_akun', PositionEnum::CREDIT->value)
        ->sum('nominal');

        $modalDebit = DetailJurnal::whereHas(
            'akun',
            function ($q) {
                $q->where('nama_akun', 'Dana Alokasi Pembiayaan Murabahah');
            }
        )
        ->where('posisi_akun', PositionEnum::DEBIT->value)
        ->sum('nominal');

        return $modalDebit - $modalCredit;
    }

    public function getDataOpsi()
    {
        return [
            'educations' => array_column(EducationEnum::cases(), 'value'),
            'marriageStatuses' => array_column(MaritalStatusEnum::cases(), 'value'),
            'incomes' => array_column(KeuanganAnggotaIncomeEnum::cases(), 'value'),
            'expenses' => array_column(KeuanganAnggotaCostEnum::cases(), 'value'),
            'hubungans' => array_column(AhliWarisEnum::cases(), 'value'),
            'conditions' => array_column(ConditionEnum::cases(), 'value'),
            'jenisBarang' => DB::table('jenis_barang')->select('id', 'nama_jenis_barang')->get(),
            'pemasok' => DB::table('pemasok')->select('id', 'nama_pemasok', 'alamat_pemasok')->get(),
            'margin_percentage' => PengaturanUmum::where('key', 'murabahah_margin_percentage')->where('tgl_diberlakukan', '<=', now())->latest()->first()?->value,
        ];
    }

    public function getDraftPembiayaan($id)
    {
        return Pembiayaan::where('id', $id)
            ->whereIn('status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::REJECTED->value,
                FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
            ])
            ->with([
                'anggota.user',
                'anggota.keuanganAnggota',
                'anggota.dokumenAnggota',
                'anggota.ahliWaris',
                'anggota.pekerjaanAnggota',
                'objekPembiayaan.jenisBarang',
                'objekPembiayaan.pemasok',
                'jaminan',
                'wakalah',
            'verification.verifier'
            ])
            ->first();
    }

    public function getTotalPembiayaanBerlangsung()
    {
        return Pembiayaan::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->count();
    }

    public function getPembiayaanBelumDireview($id)
    {
        return Pembiayaan::where('id', $id)
            ->where('status', FinancingReqStatusEnum::PENDING_REVIEW->value)
            ->with([
                'anggota.user',
                'anggota.keuanganAnggota',
                'anggota.dokumenAnggota',
                'anggota.ahliWaris',
                'anggota.pekerjaanAnggota',
                'objekPembiayaan.jenisBarang',
                'objekPembiayaan.pemasok',
                'jaminan',
                'wakalah',
            ])
            ->first();
    }

    public function syncMemberData(Pengguna $user, array $memberData, Request $request): void
    {
        $user->update([
            'nama'         => $memberData['nama'],
            'nik'          => $memberData['nik'],
            'email'        => $memberData['email'] ?? $user->email,
            'no_telp' => $memberData['no_telp'] ?? $user->no_telp,
        ]);

        $user->anggota->update([
            'jenis_kelamin'               => $memberData['jenis_kelamin'] ?? $user->anggota->jenis_kelamin,
            'tempat_lahir'          => $memberData['tempat_lahir'] ?? $user->anggota->tempat_lahir,
            'tgl_lahir'           => $memberData['tgl_lahir'] ?? $user->anggota->tgl_lahir,
            'pendidikan_terakhir'       => $memberData['pendidikan_terakhir'] ?? $user->anggota->pendidikan_terakhir,
            'alamat_domisili'     => $memberData['alamat_domisili'] ?? $user->anggota->alamat_domisili,
            'alamat_ktp'  => $memberData['alamat_ktp'] ?? $user->anggota->alamat_ktp,
            'status_pernikahan'       => $memberData['status_pernikahan'] ?? $user->anggota->status_pernikahan,
            'jml_tanggungan'           => $memberData['jml_tanggungan'] ?? $user->anggota->jml_tanggungan,
        ]);

        // Sync ahli_waris
        if (!empty($memberData['ahli_waris'])) {
            $syncData = [];

            foreach ($memberData['ahli_waris'] as $heirInput) {
                $ahli_waris = AhliWaris::firstOrCreate(
                    ['nik_ahli_waris' => $heirInput['nik_ahli_waris']],
                    [
                        'nama_ahli_waris' => $heirInput['nama_ahli_waris'],
                        'kontak_ahli_waris' => $heirInput['kontak_ahli_waris'] ?? null,
                    ]
                );

                $syncData[$ahli_waris->nik_ahli_waris] = ['hubungan' => $heirInput['hubungan']];
            }

            $user->anggota->ahliWaris()->sync($syncData);
        } else {
            $user->anggota->ahliWaris()->detach();
        }

        // Sync documents
        foreach (['slip_gaji' => 'income_slip_file', 'buku_tabungan' => 'bank_book_file'] as $docName => $fileField) {
            if ($request->hasFile($fileField)) {
                $user->anggota->dokumenAnggota()->updateOrCreate(
                    ['nama_dokumen' => $docName],
                    ['lampiran_dokumen' => $request->file($fileField)->store('documents', 'public')]
                );
            }
        }

        // Sync keuanganAnggota
        $user->anggota->keuanganAnggota()->delete();
        KeuanganAnggota::create([
            'anggota_id'                    => $user->anggota->id,
            'jml_gaji_pokok'            => $memberData['jml_gaji_pokok'] ?? 0,
            'jml_penghasilan_usaha'     => $memberData['jml_penghasilan_usaha'] ?? 0,
            'jml_penghasilan_pasangan'  => $memberData['jml_penghasilan_pasangan'] ?? 0,
            'jml_penghasilan_lainnya'   => $memberData['jml_penghasilan_lainnya'] ?? 0,
            'jml_biaya_hidup_keluarga'  => $memberData['jml_biaya_hidup_keluarga'] ?? 0,
            'jml_biaya_pendidikan'      => $memberData['jml_biaya_pendidikan'] ?? 0,
            'jml_cicilan'        => $memberData['jml_cicilan'] ?? 0,
            'jumlah_tanggungan_amount'     => $memberData['jumlah_tanggungan_amount'] ?? 0,
            'jml_biaya_lainnya'  => $memberData['jml_biaya_lainnya'] ?? 0,
        ]);

        // Sync job
        $user->anggota->pekerjaanAnggota()->delete();
        if (isset($memberData['jabatan_pekerjaan'])) {
            $user->anggota->pekerjaanAnggota()->create([
                'status_pekerjaan'        => $memberData['status_pekerjaan'] ?? null,
                'jabatan_pekerjaan'                => $memberData['jabatan_pekerjaan'] ?? null,
                'nama_perusahaan' => $memberData['nama_perusahaan'] ?? null,
                'bidang_usaha'           => $memberData['bidang_usaha'] ?? null,
                'lama_bekerja'              => $memberData['lama_bekerja'] ?? null,
                'alamat_tempat_bekerja'        => $memberData['alamat_tempat_bekerja'] ?? null,
                'no_telp_kantor'        => $memberData['no_telp_kantor'] ?? null,
            ]);
        }
    }

    public function syncFinancingData(Pengguna $user, Request $request, string $updatedBy): ?Pembiayaan
    {
        if (!isset($request['pembiayaan']['nama_barang'])) return null;

        $financingData  = $request['pembiayaan'];
        $pemasokData   = $request['pemasok'] ?? null;
        $jaminanData = $request['jaminan'] ?? null;

        $existingFinancing = Pembiayaan::where('anggota_id', $user->anggota->id)
            ->whereIn('status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::REJECTED->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
            ])
            ->latest()
            ->first();

        if ($existingFinancing) {
            // Update yang sudah ada
            $existingFinancing->update([
                'uang_muka'   => $financingData['uang_muka'] ?? 0,
                'tgl_akad'      => $financingData['tgl_akad'] ?? null,
                'harga_perolehan'     => $financingData['harga_perolehan'] ?? null,
                'margin_keuntungan'  => $financingData['margin_keuntungan'] ?? null,
                'metode_pembayaran' => $financingData['metode_pembayaran'] ?? null,
                'updated_by'     => $updatedBy,
                'harga_perkiraan' => $financingData['harga_perkiraan'] ?? null,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                'dokumen_akad' => $request->hasFile('akad_document_file') ? $request->file('akad_document_file')->store('documents', 'public') : $existingFinancing->dokumen_akad ?? null,
            ]);

            if (($financingData['metode_pembayaran'] ?? null) === FinancingPaymentMethodEnum::INSTALLMENT->value) {
                $existingFinancing->update([
                    'tenor' => $financingData['tenor'] ?? null,
                ]);
            }
            $pembiayaan = $existingFinancing;
        } else {
            // Buat baru kalau memang belum ada sama sekali
            $pembiayaan = Pembiayaan::create([
                'anggota_id'      => $user->anggota->id,
                'uang_muka'   => $financingData['uang_muka'] ?? 0,
                'harga_perkiraan' => $financingData['harga_perkiraan'] ?? null,
                'harga_perolehan'     => $financingData['harga_perolehan'] ?? null,
                'margin_keuntungan'  => $financingData['margin_keuntungan'] ?? null,
                'tgl_akad'      => $financingData['tgl_akad'] ?? null,
                'metode_pembayaran' => $financingData['metode_pembayaran'] ?? null,
                'tenor'          => $financingData['tenor'] ?? null,
                'updated_by'     => $updatedBy,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            ]);
        }

        if ($pembiayaan->status === FinancingReqStatusEnum::PENDING_REVIEW->value) {
            $pembiayaan->update(['tgl_permohonan' => now()]);
        }

        $pemasok = null;
        if ($pemasokData && isset($pemasokData['nama_pemasok'])) {
            $pemasok = Pemasok::updateOrCreate(
                ['nama_pemasok' => $pemasokData['nama_pemasok']],
                ['alamat_pemasok' => $pemasokData['alamat_pemasok'] ?? null,
                'contact' => $pemasokData['contact'] ?? null]
            );
        }

        ObjekPembiayaan::updateOrCreate(
            ['pembiayaan_id' => $pembiayaan->id],
            [
                'nama_barang'            => $financingData['nama_barang'] ?? null,
                'spesifikasi_barang'   => $financingData['spesifikasi_barang'] ?? null,
                'kuantitas'             => $financingData['kuantitas'] ?? null,
                'kondisi_produk'       => $financingData['kondisi_produk'] ?? null,
                'harga_beli_per_unit'  => $financingData['harga_beli_per_unit'] ?? null,
                'jenis_barang_id' => $financingData['jenis_barang_id'] ?? null,
                'pemasok_id'     => $financingData['pemasok_id'] ?? null,
                'struk_pembelian' => $request->hasFile('purchase_receipt_file') ? $request->file('purchase_receipt_file')->store('documents', 'public') : null,
            ]
        );

        if (isset($financingData['akad_wakalah_date'])) {
            $wakalah = Wakalah::updateOrCreate(
                ['pembiayaan_id' => $pembiayaan->id],
                [
                    'tgl_akad'       => $financingData['akad_wakalah_date'] ?? null,
                ]
            );
            if ($request->hasFile('akad_wakalah_file')) {
                $wakalah->update([
                    'dokumen_akad' => $request->file('akad_wakalah_file')->store('documents', 'public'),
                ]);
            }
        }

        if ($jaminanData && isset($jaminanData['jenis_jaminan'])) {
            $pembiayaan->jaminan()->updateOrCreate(
                ['pembiayaan_id' => $pembiayaan->id],
                [
                    'jenis_jaminan'        => $jaminanData['jenis_jaminan'],
                    'nama_pemilik'             => $jaminanData['nama_pemilik'] ?? null,
                    'nilai_perkiraan_pasar' => $jaminanData['nilai_perkiraan_pasar'] ?? null,
                    'lokasi_kondisi_jaminan'    => $jaminanData['lokasi_kondisi_jaminan'] ?? null,
                ]
            );
        }

        return $pembiayaan;
    }

    public function generateInstallments(Pembiayaan $pembiayaan): void
    {
        if (!$pembiayaan->tenor) return;

        $installmentAmount = ($pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan - $pembiayaan->uang_muka) / $pembiayaan->tenor;
        for ($i = 1; $i <= $pembiayaan->tenor; $i++) {
            Angsuran::create([
                'pembiayaan_id'   => $pembiayaan->id,
                'angsuran_ke' => $i,
                'nominal_angsuran'         => round($installmentAmount, 2),
                'tgl_jatuh_tempo'       => $pembiayaan->tgl_akad->addMonths($i),
                'status'         => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
            ]);
        }
    }

    public function formatMemberData(Anggota $anggota): array
    {
        return [
            'id' => $anggota->id,
            'kode_pengguna' => $anggota->user->kode_pengguna,
            'nama' => $anggota->user->nama,
            'email' => $anggota->user->email,
            'nik' => $anggota->user->nik,
            'no_telp' => $anggota->user->no_telp,
            'jenis_kelamin' => $anggota->jenis_kelamin,
            'tempat_lahir' => $anggota->tempat_lahir,
            'tgl_lahir' => $anggota->tgl_lahir,
            'status_pernikahan' => $anggota->status_pernikahan,
            'pendidikan_terakhir' => $anggota->pendidikan_terakhir,
            'jml_tanggungan' => $anggota->jml_tanggungan,
            'alamat_domisili' => $anggota->alamat_domisili,
            'alamat_ktp' => $anggota->alamat_ktp,
            'status_pekerjaan' => $anggota->pekerjaanAnggota?->status_pekerjaan,
            'jabatan_pekerjaan' => $anggota->pekerjaanAnggota?->jabatan_pekerjaan,
            'nama_perusahaan' => $anggota->pekerjaanAnggota?->nama_perusahaan,
            'bidang_usaha' => $anggota->pekerjaanAnggota?->bidang_usaha,
            'lama_bekerja' => $anggota->pekerjaanAnggota?->lama_bekerja,
            'alamat_tempat_bekerja' => $anggota->pekerjaanAnggota?->alamat_tempat_bekerja,
            'no_telp_kantor' => $anggota->pekerjaanAnggota?->no_telp_kantor,
            'jml_gaji_pokok' => $anggota->keuanganAnggota?->jml_gaji_pokok ?? 0,
            'jml_penghasilan_usaha' => $anggota->keuanganAnggota?->jml_penghasilan_usaha ?? 0,
            'jml_penghasilan_pasangan' => $anggota->keuanganAnggota?->jml_penghasilan_pasangan ?? 0,
            'jml_penghasilan_lainnya' => $anggota->keuanganAnggota?->jml_penghasilan_lainnya ?? 0,
            'jml_biaya_hidup_keluarga' => $anggota->keuanganAnggota?->jml_biaya_hidup_keluarga ?? 0,
            'jml_biaya_pendidikan' => $anggota->keuanganAnggota?->jml_biaya_pendidikan ?? 0,
            'jml_cicilan' => $anggota->keuanganAnggota?->jml_cicilan ?? 0,
            'jml_biaya_lainnya' => $anggota->keuanganAnggota?->jml_biaya_lainnya ?? 0,
            'ahli_waris' => $anggota->ahliWaris->map(fn($h) => [
                'nik_ahli_waris' => $h->nik_ahli_waris,
                'nama_ahli_waris' => $h->nama_ahli_waris,
                'hubungan' => $h->pivot->hubungan,
                'kontak_ahli_waris' => $h->kontak_ahli_waris,
            ])->values(),
        ];
    }

    public function generateTangguhSchedule(Pembiayaan $pembiayaan, $tangguhPaymentDate): void
    {
        if (!$tangguhPaymentDate) return;

        Angsuran::create([
            'pembiayaan_id'   => $pembiayaan->id,
            'angsuran_ke' => 1,
            'nominal_angsuran'         => $pembiayaan->harga_perolehan + $pembiayaan->margin_keuntungan - $pembiayaan->uang_muka,
            'tgl_jatuh_tempo'       => $tangguhPaymentDate,
            'status'         => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
        ]);
    }

    public function computepembiayaanummary(Pembiayaan $pembiayaan): void
    {
        $this->sharedPembiayaanService->computepembiayaanummary($pembiayaan);
    }

    public function computeNextDueDate(Pembiayaan $pembiayaan): void
    {
        $this->sharedPembiayaanService->computeNextDueDate($pembiayaan);
    }

}
