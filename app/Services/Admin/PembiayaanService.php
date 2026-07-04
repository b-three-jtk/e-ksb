<?php
namespace App\Services\Admin;

use App\Enums\ConditionEnum;
use App\Enums\EducationEnum;
use App\Enums\FinancialCostEnum;
use App\Enums\FinancialIncomeEnum;
use App\Enums\FinancingPaymentMethodEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PositionEnum;
use App\Models\Financial;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\GlobalSetting;
use App\Models\Heir;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\Anggota;
use App\Models\Supplier;
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
        return Financing::with([
            'anggota.user' => function ($query) {
                $query->select('id', 'nama', 'kode_pengguna');
            },
            'installment',
            'financingItem.jenisBarang' => function ($query) {
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
        return Financing::whereIn('status', [
            FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            FinancingReqStatusEnum::PENDING_REVIEW->value,
            FinancingReqStatusEnum::APPROVED->value,
            FinancingReqStatusEnum::REJECTED->value,
            FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
        ])->count();
    }

    public function getModalBelumDiputar()
    {
        $modalCredit = JournalEntry::whereHas(
            'account',
            function ($q) {
                $q->where('account_name', 'Dana Alokasi Pembiayaan Murabahah');
            }
        )
        ->where('position', PositionEnum::CREDIT->value)
        ->sum('nominal');

        $modalDebit = JournalEntry::whereHas(
            'account',
            function ($q) {
                $q->where('account_name', 'Dana Alokasi Pembiayaan Murabahah');
            }
        )
        ->where('position', PositionEnum::DEBIT->value)
        ->sum('nominal');

        return $modalDebit - $modalCredit;
    }

    public function getDataOpsi()
    {
        return [
            'educations' => array_column(EducationEnum::cases(), 'value'),
            'marriageStatuses' => array_column(MaritalStatusEnum::cases(), 'value'),
            'incomes' => array_column(FinancialIncomeEnum::cases(), 'value'),
            'expenses' => array_column(FinancialCostEnum::cases(), 'value'),
            'relationships' => array_column(HeirEnum::cases(), 'value'),
            'conditions' => array_column(ConditionEnum::cases(), 'value'),
            'jenisBarang' => DB::table('jenis_barang')->select('id', 'nama_jenis_barang')->get(),
            'suppliers' => DB::table('suppliers')->select('id', 'supplier_name', 'address')->get(),
            'margin_percentage' => GlobalSetting::where('key', 'murabahah_margin_percentage')->where('effective_date', '<=', now())->latest()->first()?->value,
        ];
    }

    public function getDraftPembiayaan($id)
    {
        return Financing::where('id', $id)
            ->whereIn('status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::REJECTED->value,
                FinancingReqStatusEnum::APPROVED_WITH_CONDITIONS->value,
            ])
            ->with([
                'anggota.user',
                'anggota.financials',
                'anggota.memberDocs',
                'anggota.heirs',
                'anggota.memberJobs',
                'financingItem.jenisBarang',
                'financingItem.supplier',
                'collateral',
                'wakalah',
            'verification.verifier'
            ])
            ->first();
    }

    public function getTotalPembiayaanBerlangsung()
    {
        return Financing::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->count();
    }

    public function getPembiayaanBelumDireview($id)
    {
        return Financing::where('id', $id)
            ->where('status', FinancingReqStatusEnum::PENDING_REVIEW->value)
            ->with([
                'anggota.user',
                'anggota.financials',
                'anggota.memberDocs',
                'anggota.heirs',
                'anggota.memberJobs',
                'financingItem.jenisBarang',
                'financingItem.supplier',
                'collateral',
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

        // Sync heirs
        if (!empty($memberData['heirs'])) {
            $syncData = [];

            foreach ($memberData['heirs'] as $heirInput) {
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
        } else {
            $user->anggota->heirs()->detach();
        }

        // Sync documents
        foreach (['slip_gaji' => 'income_slip_file', 'buku_tabungan' => 'bank_book_file'] as $docName => $fileField) {
            if ($request->hasFile($fileField)) {
                $user->anggota->memberDocs()->updateOrCreate(
                    ['doc_name' => $docName],
                    ['doc_attachment' => $request->file($fileField)->store('documents', 'public')]
                );
            }
        }

        // Sync financials
        $user->anggota->financials()->delete();
        Financial::create([
            'anggota_id'                    => $user->anggota->id,
            'gaji_pokok_amount'            => $memberData['gaji_pokok_amount'] ?? 0,
            'penghasilan_usaha_amount'     => $memberData['penghasilan_usaha_amount'] ?? 0,
            'penghasilan_pasangan_amount'  => $memberData['penghasilan_pasangan_amount'] ?? 0,
            'penghasilan_lainnya_amount'   => $memberData['penghasilan_lainnya_amount'] ?? 0,
            'biaya_hidup_keluarga_amount'  => $memberData['biaya_hidup_keluarga_amount'] ?? 0,
            'biaya_pendidikan_amount'      => $memberData['biaya_pendidikan_amount'] ?? 0,
            'jumlah_cicilan_amount'        => $memberData['jumlah_cicilan_amount'] ?? 0,
            'jumlah_tanggungan_amount'     => $memberData['jumlah_tanggungan_amount'] ?? 0,
            'jumlah_biaya_lainnya_amount'  => $memberData['jumlah_biaya_lainnya_amount'] ?? 0,
        ]);

        // Sync job
        $user->anggota->memberJobs()->delete();
        if (isset($memberData['job_title'])) {
            $user->anggota->memberJobs()->create([
                'employment_status'        => $memberData['employment_status'] ?? null,
                'job_title'                => $memberData['job_title'] ?? null,
                'company_or_business_name' => $memberData['company_or_business_name'] ?? null,
                'business_field'           => $memberData['business_field'] ?? null,
                'tenure_year'              => $memberData['tenure_year'] ?? null,
                'workplace_address'        => $memberData['workplace_address'] ?? null,
                'workplace_contact'        => $memberData['workplace_contact'] ?? null,
            ]);
        }
    }

    public function syncFinancingData(Pengguna $user, Request $request, string $updatedBy): ?Financing
    {
        if (!isset($request['financing']['name'])) return null;

        $financingData  = $request['financing'];
        $supplierData   = $request['supplier'] ?? null;
        $collateralData = $request['collateral'] ?? null;

        $existingFinancing = Financing::where('anggota_id', $user->anggota->id)
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
                'down_payment'   => $financingData['down_payment'] ?? 0,
                'akad_date'      => $financingData['akad_date'] ?? null,
                'cost_price'     => $financingData['cost_price'] ?? null,
                'margin_amount'  => $financingData['margin_amount'] ?? null,
                'payment_method' => $financingData['payment_method'] ?? null,
                'updated_by'     => $updatedBy,
                'predicted_cost_price' => $financingData['predicted_cost_price'] ?? null,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                'signed_akad_document' => $request->hasFile('akad_document_file') ? $request->file('akad_document_file')->store('documents', 'public') : $existingFinancing->signed_akad_document ?? null,
            ]);

            if (($financingData['payment_method'] ?? null) === FinancingPaymentMethodEnum::INSTALLMENT->value) {
                $existingFinancing->update([
                    'tenor' => $financingData['tenor'] ?? null,
                ]);
            }
            $financing = $existingFinancing;
        } else {
            // Buat baru kalau memang belum ada sama sekali
            $financing = Financing::create([
                'anggota_id'      => $user->anggota->id,
                'down_payment'   => $financingData['down_payment'] ?? 0,
                'predicted_cost_price' => $financingData['predicted_cost_price'] ?? null,
                'cost_price'     => $financingData['cost_price'] ?? null,
                'margin_amount'  => $financingData['margin_amount'] ?? null,
                'akad_date'      => $financingData['akad_date'] ?? null,
                'payment_method' => $financingData['payment_method'] ?? null,
                'tenor'          => $financingData['tenor'] ?? null,
                'updated_by'     => $updatedBy,
                'status'         => $financingData['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
            ]);
        }

        if ($financing->status === FinancingReqStatusEnum::PENDING_REVIEW->value) {
            $financing->update(['requested_date' => now()]);
        }

        $supplier = null;
        if ($supplierData && isset($supplierData['supplier_name'])) {
            $supplier = Supplier::updateOrCreate(
                ['supplier_name' => $supplierData['supplier_name']],
                ['address' => $supplierData['address'] ?? null,
                'contact' => $supplierData['contact'] ?? null]
            );
        }

        FinancingItem::updateOrCreate(
            ['financing_id' => $financing->id],
            [
                'name'            => $financingData['name'] ?? null,
                'specification'   => $financingData['specification'] ?? null,
                'qty'             => $financingData['qty'] ?? null,
                'condition'       => $financingData['condition'] ?? null,
                'price_per_unit'  => $financingData['price_per_unit'] ?? null,
                'jenis_barang_id' => $financingData['jenis_barang_id'] ?? null,
                'supplier_id'     => $financingData['supplier_id'] ?? null,
                'purchase_receipt' => $request->hasFile('purchase_receipt_file') ? $request->file('purchase_receipt_file')->store('documents', 'public') : null,
            ]
        );

        if (isset($financingData['akad_wakalah_date'])) {
            $wakalah = Wakalah::updateOrCreate(
                ['financing_id' => $financing->id],
                [
                    'akad_date'       => $financingData['akad_wakalah_date'] ?? null,
                ]
            );
            if ($request->hasFile('akad_wakalah_file')) {
                $wakalah->update([
                    'signed_akad_document' => $request->file('akad_wakalah_file')->store('documents', 'public'),
                ]);
            }
        }

        if ($collateralData && isset($collateralData['collateral_type'])) {
            $financing->collateral()->updateOrCreate(
                ['financing_id' => $financing->id],
                [
                    'collateral_type'        => $collateralData['collateral_type'],
                    'owner_name'             => $collateralData['owner_name'] ?? null,
                    'estimated_market_value' => $collateralData['estimated_market_value'] ?? null,
                    'collateral_location'    => $collateralData['collateral_location'] ?? null,
                ]
            );
        }

        return $financing;
    }

    public function generateInstallments(Financing $financing): void
    {
        if (!$financing->tenor) return;

        $installmentAmount = ($financing->cost_price + $financing->margin_amount - $financing->down_payment) / $financing->tenor;
        for ($i = 1; $i <= $financing->tenor; $i++) {
            Installment::create([
                'financing_id'   => $financing->id,
                'installment_no' => $i,
                'amount'         => round($installmentAmount, 2),
                'due_date'       => $financing->akad_date->addMonths($i),
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
            'employment_status' => $anggota->memberJobs?->employment_status,
            'job_title' => $anggota->memberJobs?->job_title,
            'company_or_business_name' => $anggota->memberJobs?->company_or_business_name,
            'business_field' => $anggota->memberJobs?->business_field,
            'tenure_year' => $anggota->memberJobs?->tenure_year,
            'workplace_address' => $anggota->memberJobs?->workplace_address,
            'workplace_contact' => $anggota->memberJobs?->workplace_contact,
            'gaji_pokok_amount' => $anggota->financials?->gaji_pokok_amount ?? 0,
            'penghasilan_usaha_amount' => $anggota->financials?->penghasilan_usaha_amount ?? 0,
            'penghasilan_pasangan_amount' => $anggota->financials?->penghasilan_pasangan_amount ?? 0,
            'penghasilan_lainnya_amount' => $anggota->financials?->penghasilan_lainnya_amount ?? 0,
            'biaya_hidup_keluarga_amount' => $anggota->financials?->biaya_hidup_keluarga_amount ?? 0,
            'biaya_pendidikan_amount' => $anggota->financials?->biaya_pendidikan_amount ?? 0,
            'jumlah_cicilan_amount' => $anggota->financials?->jumlah_cicilan_amount ?? 0,
            'jumlah_biaya_lainnya_amount' => $anggota->financials?->jumlah_biaya_lainnya_amount ?? 0,
            'heirs' => $anggota->heirs->map(fn($h) => [
                'heir_nik' => $h->heir_nik,
                'heir_name' => $h->heir_name,
                'relationship' => $h->pivot->relationship,
                'heir_contact' => $h->heir_contact,
            ])->values(),
        ];
    }

    public function generateTangguhSchedule(Financing $financing, $tangguhPaymentDate): void
    {
        if (!$tangguhPaymentDate) return;

        Installment::create([
            'financing_id'   => $financing->id,
            'installment_no' => 1,
            'amount'         => $financing->cost_price + $financing->margin_amount - $financing->down_payment,
            'due_date'       => $tangguhPaymentDate,
            'status'         => InstallmentPaymentScheduleStatusEnum::SCHEDULED->value,
        ]);
    }

    public function computeFinancingSummary(Financing $financing): void
    {
        $this->sharedPembiayaanService->computeFinancingSummary($financing);
    }

    public function computeNextDueDate(Financing $financing): void
    {
        $this->sharedPembiayaanService->computeNextDueDate($financing);
    }

}
