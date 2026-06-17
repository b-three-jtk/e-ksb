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
use App\Models\InstallmentPaymentTransaction;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MemberDoc;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wakalah;
use App\Services\PembiayaanService as SharedPembiayaanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PembiayaanService
{
    public function __construct(private SharedPembiayaanService $sharedPembiayaanService)
    {
    }

    public function getSemuaPembiayaan($search, $tab, $verifier)
    {
        return Financing::with([
            'member.user' => function ($query) {
                $query->select('id', 'name', 'user_code');
            },
            'installment',
            'financingItem.productType' => function ($query) {
                $query->select('product_types.id', 'product_types.product_type_name');
            }
        ])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('member.user', function ($userQuery) use ($search) {
                    $userQuery->where(function ($userSearchQuery) use ($search) {
                        $userSearchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('user_code', 'like', "%{$search}%");
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
                    $q->where('status', FinancingReqStatusEnum::WAITING_DOCUMENTS->value);
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
            'productTypes' => DB::table('product_types')->select('id', 'product_type_name')->get(),
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
                'member.user',
                'member.financials',
                'member.memberDocs',
                'member.heirs',
                'member.memberJobs',
                'financingItem.productType',
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
                'member.user',
                'member.financials',
                'member.memberDocs',
                'member.heirs',
                'member.memberJobs',
                'financingItem.productType',
                'financingItem.supplier',
                'collateral',
                'wakalah',
            ])
            ->first();
    }

    public function syncMemberData(User $user, array $memberData, Request $request): void
    {
        $user->update([
            'name'         => $memberData['name'],
            'nik'          => $memberData['nik'],
            'email'        => $memberData['email'] ?? $user->email,
            'phone_number' => $memberData['phone_number'] ?? $user->phone_number,
        ]);

        $user->member->update([
            'gender'               => $memberData['gender'] ?? $user->member->gender,
            'birth_place'          => $memberData['birth_place'] ?? $user->member->birth_place,
            'birth_date'           => $memberData['birth_date'] ?? $user->member->birth_date,
            'last_education'       => $memberData['last_education'] ?? $user->member->last_education,
            'domicile_address'     => $memberData['domicile_address'] ?? $user->member->domicile_address,
            'residential_address'  => $memberData['residential_address'] ?? $user->member->residential_address,
            'marital_status'       => $memberData['marital_status'] ?? $user->member->marital_status,
            'dependents'           => $memberData['dependents'] ?? $user->member->dependents,
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

            $user->member->heirs()->sync($syncData);
        } else {
            $user->member->heirs()->detach();
        }

        // Sync documents
        foreach (['slip_gaji' => 'income_slip_file', 'buku_tabungan' => 'bank_book_file'] as $docName => $fileField) {
            if ($request->hasFile($fileField)) {
                $user->member->memberDocs()->updateOrCreate(
                    ['doc_name' => $docName],
                    ['doc_attachment' => $request->file($fileField)->store('documents', 'public')]
                );
            }
        }

        // Sync financials
        $user->member->financials()->delete();
        Financial::create([
            'member_id'                    => $user->member->id,
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
        $user->member->memberJobs()->delete();
        if (isset($memberData['job_title'])) {
            $user->member->memberJobs()->create([
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

    public function syncFinancingData(User $user, array $validated, Request $request, string $updatedBy): ?Financing
    {
        if (!isset($validated['financing']['name'])) return null;

        $financingData  = $validated['financing'];
        $supplierData   = $validated['supplier'] ?? null;
        $collateralData = $validated['collateral'] ?? null;

        $existingFinancing = Financing::where('member_id', $user->member->id)
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
                'member_id'      => $user->member->id,
                'tenor'          => $financingData['tenor'] ?? null,
                'down_payment'   => $financingData['down_payment'] ?? 0,
                'akad_date'      => $financingData['akad_date'] ?? null,
                'cost_price'     => $financingData['cost_price'] ?? null,
                'margin_amount'  => $financingData['margin_amount'] ?? null,
                'payment_method' => $financingData['payment_method'] ?? null,
                'predicted_cost_price' => $financingData['predicted_cost_price'] ?? null,
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

        $financingItem = FinancingItem::updateOrCreate(
            ['financing_id' => $financing->id],
            [
                'name'            => $financingData['name'] ?? null,
                'specification'   => $financingData['specification'] ?? null,
                'qty'             => $financingData['qty'] ?? null,
                'condition'       => $financingData['condition'] ?? null,
                'price_per_unit'  => $financingData['price_per_unit'] ?? null,
                'product_type_id' => $financingData['product_type_id'] ?? null,
                'supplier_id'     => $financingData['supplier_id'] ?? null,
            ]
        );

        if ($request->hasFile('purchase_receipt_file')) {
            $financingItem->update([
                'purchase_receipt' => $request->file('purchase_receipt_file')->store('documents', 'public'),
            ]);
        }

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

    public function formatMemberData(Member $member): array
    {
        return [
            'id' => $member->id,
            'user_code' => $member->user->user_code,
            'name' => $member->user->name,
            'email' => $member->user->email,
            'nik' => $member->user->nik,
            'phone_number' => $member->user->phone_number,
            'gender' => $member->gender,
            'birth_place' => $member->birth_place,
            'birth_date' => $member->birth_date,
            'marital_status' => $member->marital_status,
            'last_education' => $member->last_education,
            'dependents' => $member->dependents,
            'domicile_address' => $member->domicile_address,
            'residential_address' => $member->residential_address,
            'employment_status' => $member->memberJobs?->employment_status,
            'job_title' => $member->memberJobs?->job_title,
            'company_or_business_name' => $member->memberJobs?->company_or_business_name,
            'business_field' => $member->memberJobs?->business_field,
            'tenure_year' => $member->memberJobs?->tenure_year,
            'workplace_address' => $member->memberJobs?->workplace_address,
            'workplace_contact' => $member->memberJobs?->workplace_contact,
            'gaji_pokok_amount' => $member->financials?->gaji_pokok_amount ?? 0,
            'penghasilan_usaha_amount' => $member->financials?->penghasilan_usaha_amount ?? 0,
            'penghasilan_pasangan_amount' => $member->financials?->penghasilan_pasangan_amount ?? 0,
            'penghasilan_lainnya_amount' => $member->financials?->penghasilan_lainnya_amount ?? 0,
            'biaya_hidup_keluarga_amount' => $member->financials?->biaya_hidup_keluarga_amount ?? 0,
            'biaya_pendidikan_amount' => $member->financials?->biaya_pendidikan_amount ?? 0,
            'jumlah_cicilan_amount' => $member->financials?->jumlah_cicilan_amount ?? 0,
            'jumlah_biaya_lainnya_amount' => $member->financials?->jumlah_biaya_lainnya_amount ?? 0,
            'heirs' => $member->heirs->map(fn($h) => [
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

    public function getCreatePaymentData(Financing $financing): array
    {
        $financing->load([
            'member.user',
            'financingItem.productType',
            'installment',
        ]);

        $paidStatuses = [
            InstallmentPaymentScheduleStatusEnum::PAID->value,
            InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
        ];

        $installment = Installment::where('financing_id', $financing->id)
            ->whereNotIn('status', $paidStatuses)
            ->orderBy('installment_no')
            ->first();

        $nextInstallment = Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>', $installment?->installment_no)
            ->orderBy('installment_no')
            ->first();

        $hargaJual     = $financing->cost_price + $financing->margin_amount;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('installment', fn($q) =>
            $q->where('financing_id', $financing->id)
        )->sum('nominal');

        $sisa         = $hargaJual - $totalTerbayar;
        $paymentCount = InstallmentPaymentTransaction::where('installment_id', $installment?->id)->count();

        return [
            'id'                      => $financing->id,
            'transaction_code'        => $financing->financing_transaction_code,
            'product_name'            => $financing->financingItem?->name,
            'product_type'            => $financing->financingItem?->productType?->product_type_name,
            'product_specification'   => $financing->financingItem?->specification,
            'color'                   => '-',
            'qty'                     => $financing->financingItem?->qty,
            'user' => [
                'name'      => $financing->member?->user?->name,
                'user_code' => $financing->member?->user?->user_code,
            ],
            'installment_per_month'   => $installment?->amount ?? 0,
            'remaining_balance'       => max($sisa, 0),
            'next_installment_number' => $installment?->installment_no,
            'current_due_date'        => $installment?->due_date?->format('Y-m-d'),
            'payment_count'           => $paymentCount + 1,
            'next_due_date'           => $nextInstallment?->due_date?->format('Y-m-d'),
            'financing_id'            => $financing->id,
            'installment_id'          => $installment?->id,
        ];
    }

    public function processPayment(array $validated): array
    {
        $financing = Financing::with([
            'member.user',
            'financingItem.productType',
            'installment',
        ])->findOrFail($validated['financing_id']);

        $marginPerMonth    = round($financing->margin_amount / $financing->tenor, 2);
        $principalPerMonth = round($validated['nominal'] - $marginPerMonth, 2);

        $payment = InstallmentPaymentTransaction::create([
            'installment_trans_code'  => 'INS' . strtoupper(substr(uniqid(), -7)),
            'payment_method'          => $validated['payment_method'],
            'is_early_repayment'      => false,
            'nominal'                 => $validated['nominal'],
            'principal_amount'        => $principalPerMonth,
            'margin_amount'           => $marginPerMonth,
            'payment_date'            => $validated['payment_date'],
            'installment_id'          => $validated['installment_id'],
            'updated_by'              => auth()->id(),
        ]);

        $installment = Installment::findOrFail($validated['installment_id']);
        $paymentDate = Carbon::parse($validated['payment_date']);
        $dueDate     = $installment->due_date;

        $status = $paymentDate->startOfDay()->gt($dueDate->copy()->startOfDay())
            ? InstallmentPaymentScheduleStatusEnum::OVERDUE->value
            : InstallmentPaymentScheduleStatusEnum::PAID->value;

        $installment->update(['status' => $status]);

        $hargaJual     = $financing->cost_price + $financing->margin_amount;
        $totalTerbayar = InstallmentPaymentTransaction::whereHas('installment', fn($q) =>
            $q->where('financing_id', $financing->id)
        )->sum('nominal');

        $sisa = $hargaJual - $totalTerbayar;

        if ($sisa <= 0) {
            $financing->update(['status' => 'Lunas']);
        }

        $nextInstallment = Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>', $installment->installment_no)
            ->orderBy('installment_no')
            ->first();

        return compact('financing', 'payment', 'installment', 'nextInstallment', 'hargaJual', 'sisa');
    }

    public function generateAndStoreReceipt(array $paymentData): ?string
    {
        [
            'financing'       => $financing,
            'payment'         => $payment,
            'installment'     => $installment,
            'nextInstallment' => $nextInstallment,
            'hargaJual'       => $hargaJual,
            'sisa'            => $sisa,
        ] = $paymentData;

        try {
            Carbon::setLocale('id');

            $logoPath = public_path('images/logo/logo-icon.svg');
            $logo = file_exists($logoPath)
                ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
                : '';

            $receipt = [
                'logo'            => $logo,
                'payment_method'  => $payment->payment_method,
                'organization' => [
                    'name'    => 'Koperasi Syariah Berkah',
                    'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                ],
                'petugas'              => auth()->user()->name,
                'tanggal_angsuran'     => Carbon::parse($payment->payment_date)->translatedFormat('d F Y'),
                'nomor_pembiayaan'     => $financing->financing_transaction_code,
                'no_anggota'           => $financing->member?->user?->user_code,
                'diterima_dari'        => $financing->member?->user?->name,
                'sejumlah_uang'        => $payment->nominal,
                'items' => [[
                    'no'         => 1,
                    'keterangan' => 'Angsuran ke ' . $installment->installment_no,
                    'jumlah'     => $payment->nominal,
                ]],
                'harga_perolehan' => $financing->cost_price,
                'margin'          => $financing->margin_amount,
                'harga_jual'      => $hargaJual,
                'total_angsuran'  => $payment->nominal,
                'sisa_hutang'     => max($sisa, 0),
                'status'          => max($sisa, 0) <= 0 ? 'Lunas' : 'Belum Lunas',
                'jatuh_tempo'     => $nextInstallment
                    ? $nextInstallment->due_date->translatedFormat('d F Y')
                    : '-',
                'catatan'         => 'Dasar akad yang digunakan adalah akad murabahah yang merupakan kontrak jual beli syariah.',
                'tanggal_cetak'   => now()->translatedFormat('d F Y'),
            ];

            $pdf = Pdf::loadView('exports.financing_payment_receipt', ['receipt' => $receipt])
                ->setPaper('a5', 'landscape')
                ->setOptions(['isRemoteEnabled' => true]);

            $fileName = 'receipts/' . $financing->member->id . '/receipt-' . time() . '.pdf';

            Storage::disk('public')->put($fileName, $pdf->output());

            MemberDoc::create([
                'member_id'      => $financing->member_id,
                'doc_name'       => 'Kwitansi Pembayaran ' . $payment->installment_trans_code,
                'doc_attachment' => $fileName,
            ]);

            $payment->update(['installment_payment_receipt' => $fileName]);

            return $fileName;

        } catch (\Throwable $th) {
            Log::error('PDF generation failed: ' . $th->getMessage());
            return null;
        }
    }

    public function rescheduleInstallments(Financing $financing, string $installmentId, string $newDueDate): void
    {
        $currentInstallment = Installment::findOrFail($installmentId);
        $newDate            = Carbon::parse($newDueDate);

        Installment::where('financing_id', $financing->id)
            ->where('installment_no', '>=', $currentInstallment->installment_no)
            ->orderBy('installment_no')
            ->get()
            ->each(function ($item, $index) use ($newDate) {
                $item->update(['due_date' => $newDate->copy()->addMonths($index)]);
            });
    }
}
