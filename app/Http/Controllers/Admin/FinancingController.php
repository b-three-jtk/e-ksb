<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConditionEnum;
use App\Enums\EducationEnum;
use App\Enums\FinancialCostEnum;
use App\Enums\FinancialIncomeEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PositionEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\FinancingPaymentMethodEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRepaymentRequest;
use App\Http\Requests\StoreFinancingDraftRequest;
use App\Http\Requests\StoreFinancingRequest;
use App\Models\Financing;
use App\Models\FinancingVerification;
use App\Models\Installment;
use App\Models\InstallmentPaymentTransaction;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MemberDoc;
use App\Models\SavingAccount;
use App\Models\User;
use App\Models\Account;
use App\Services\Admin\JournalService;
use App\Services\Admin\FinancingService;
use App\Services\Admin\RepaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FinancingController extends Controller
{
    public function __construct(private FinancingService $financingService){}
    private function baseQuery(Request $request)
    {
        $verifier = auth()->user();
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');

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
                ]);
            })
            ->when($tab === 'active', function ($q) {
                $q->where(
                    'status',
                    FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                );
            })->latest('updated_at');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = $this->baseQuery($request)->orderBy($sortBy, $sortDir);

        $financings = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($f) {
                return [
                    'id' => $f->id,
                    'financing_transaction_code' => $f->financing_transaction_code,
                    'akad_date' => $f->akad_date?->format('Y-m-d') ?? '',
                    'user' => $f->member->user
                        ? ($f->member->user->user_code . ' - ' . $f->member->user->name)
                        : '-',
                    'tenor_left' => $f->installment ? max(0, $f->tenor - ($f->installment->where('status', '!=', InstallmentPaymentScheduleStatusEnum::PAID->value)->count())) : null,
                    'product_name' => $f->financingItem?->name,
                    'status' => $f->status,
                ];
            });

        $summary = [
            [
                'title' => 'Total Pengajuan Pembiayaan Murabahah',
                'value' => Financing::whereIn('status', [
                    FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    FinancingReqStatusEnum::PENDING_REVIEW->value,
                    FinancingReqStatusEnum::APPROVED->value,
                    FinancingReqStatusEnum::REJECTED->value,
                ])->count()
            ],
            ['title' => 'Total Pembiayaan Berlangsung', 'value' => Financing::where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->count()],
            ['title' => 'Total Modal Belum Diputar', 'value' => $this->getModalBelumDiputar()],
        ];

        return inertia('Admin/Financing/Index', [
            'financings' => $financings,
            'summary' => $summary,
            'filters' => compact('search', 'perPage', 'tab', 'sortBy', 'sortDir'),
        ]);
    }

    private function getModalBelumDiputar()
    {
        $modalCredit = JournalEntry::where('no_ref_account', '102')
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        $modalDebit = JournalEntry::where('no_ref_account', '102')
            ->where('position', PositionEnum::DEBIT->value)
            ->sum('nominal');

        return $modalCredit - $modalDebit;
    }

    /**
     * Get common dropdown data
     */
    private function getCommonData(): array
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
        ];
    }

    /**
     * Format member data untuk response
     */
    private function formatMemberData(Member $member): array
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
                'relationship' => $h->relationship,
                'heir_contact' => $h->heir_contact,
            ])->values(),
        ];
    }

    public function show(string $id)
    {
        $financing = Financing::with(['financingItem.productType', 'installment.payment', 'financingItem.supplier', 'collateral'])->findOrFail($id);
        $financing->total_price = ($financing->cost_price ?? 0) + ($financing->margin_amount ?? 0) - ($financing->down_payment ?? 0);

        $installment = $financing->installment;

        if ($installment && $installment->count() > 0) {
            $paid_count = $installment->where('status', InstallmentPaymentScheduleStatusEnum::PAID->value)->count();
            $total_paid = $financing->tenor > 0
                ? $paid_count * (($financing->margin_amount ?? 0) + ($financing->cost_price ?? 0) - ($financing->down_payment ?? 0)) / $financing->tenor
                : 0;
            $financing->remaining_balance = $financing->total_price - $total_paid;
            $financing->total_paid = $total_paid;

            if ($financing->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
        } else {
            $financing->total_paid = 0;
            if ($installment && $financing->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
            $financing->remaining_balance = $financing->total_price;
        }

        if ($installment && $financing->akad_date) {
            $paid_count = $installment ? $installment->count() : 0;
            if ($paid_count < $financing->tenor) {
                $financing->next_due_date = Carbon::parse($financing->akad_date)
                    ->addMonthsNoOverflow($paid_count + 1)
                    ->format('Y-m-d');
            } else {
                $financing->next_due_date = null;
            }
        }

        return inertia('Admin/Financing/Show', [
            'data' => $financing
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Pre-load members dengan criteria tertentu untuk search
        $activeMembers = Member::with([
            'user:id,user_code,name,email,nik,phone_number',
            'memberDocs:id,member_id,doc_name,doc_attachment',
            'financials',
            'financings:id,member_id,status',
            'savingAccounts:id,member_id,saving_type,created_at,balance'
        ])
            ->whereHas('user', fn($q) =>
                $q->whereHas('roles', fn($roleQ) => $roleQ->where('name', 'Anggota'))
                    ->where('status', UserStatusEnum::ACTIVE->value)
            )
            ->limit(20)
            ->get()
            ->map(function ($member) {
                $hasActiveFinancing = $member->financings?->whereIn(
                    'status',
                    [
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                        FinancingReqStatusEnum::REJECTED->value,
                        FinancingReqStatusEnum::APPROVED->value,
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                        FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    ]
                )->isNotEmpty() ?? false;

                $member->is_have_no_obligation = !$hasActiveFinancing;

                $hasEligibleSaving = $member->savingAccounts
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->isNotEmpty();

                $member->is_have_eligible_saving = $hasEligibleSaving;
                $member->family_card = $member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'kk')->first()->doc_attachment) : null;
                $member->income_slip = $member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'slip_gaji')->first()->doc_attachment) : null;
                $member->bank_book = $member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'buku_tabungan')->first()->doc_attachment) : null;

                return $member;
            });

        return inertia('Admin/Financing/Create', [
            'data' => $this->getCommonData(),
            'preloadedMembers' => $activeMembers,
        ]);
    }

    public function loadDraft(string $id)
    {
        $financing = Financing::where('id', $id)
            ->whereIn('status', [
                FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                FinancingReqStatusEnum::APPROVED->value,
                FinancingReqStatusEnum::REJECTED->value,
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
            ])
            ->first();

        if (!$financing) {
            throw ValidationException::withMessages(['Data pembiayaan tidak ditemukan atau tidak dalam status yang valid untuk dimuat sebagai draft']);
        }

        return inertia('Admin/Financing/Create', [
            'data' => $this->getCommonData(),
            'financing' => [
                'member' => $this->formatMemberData($financing->member),
                'financing' => [
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'specification' => $financing->financingItem->specification,
                    'price_per_unit' => $financing->financingItem->price_per_unit,
                    'cost_price' => $financing->cost_price,
                    'margin_amount' => $financing->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'payment_method' => $financing->payment_method,
                    'akad_wakalah_date' => $financing->wakalah?->akad_date,
                    'akad_date' => $financing->akad_date,
                    'status' => $financing->status,
                    'tenor' => $financing->tenor,
                    'predicted_cost_price' => $financing->predicted_cost_price,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                    'purchase_receipt' => $this->getDocumentUrl($financing->financingItem->purchase_receipt),
                    'akad_document' => $this->getDocumentUrl($financing->signed_akad_document),
                    'akad_wakalah_document' => $this->getDocumentUrl($financing->wakalah?->signed_akad_document),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'address' => $financing->financingItem->supplier->address,
                ] : null,
            ],
        ]);
    }

    private function getDocumentUrl($path)
    {
        return $path ? asset('storage/' . $path) : null;
    }

    public function showValidation(string $id)
    {
        $financing = Financing::where('id', $id)
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

        return inertia('Admin/Financing/Validation', [
            'data' => [
                'member' => $this->formatMemberData($financing->member),
                'financing' => [
                    'id' => $financing->id,
                    'financing_transaction_code' => $financing->financing_transaction_code,
                    'name' => $financing->financingItem->name,
                    'product_type_id' => $financing->financingItem->product_type_id,
                    'condition' => $financing->financingItem->condition,
                    'qty' => $financing->financingItem->qty,
                    'specification' => $financing->financingItem->specification,
                    'cost_price' => $financing->cost_price,
                    'margin_amount' => $financing->margin_amount,
                    'supplier_id' => $financing->financingItem->supplier_id,
                    'down_payment' => $financing->down_payment,
                    'payment_method' => $financing->payment_method,
                    'akad_date' => $financing->akad_date,
                    'status' => $financing->status,
                    'product_type' => $financing->financingItem->productType?->product_type_name,
                    'tenor' => $financing->tenor,
                    'predicted_cost_price' => $financing->predicted_cost_price,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment),
                    'income_slip' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment),
                    'bank_book' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment),
                ],
                'supplier' => $financing->financingItem->supplier ? [
                    'supplier_name' => $financing->financingItem->supplier->supplier_name,
                    'address' => $financing->financingItem->supplier->address,
                ] : null,
            ],
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required',
            'notes' => 'nullable|string',
        ]);

        try {
            $financing = Financing::where('id', $id)
                ->where('status', FinancingReqStatusEnum::PENDING_REVIEW->value)
                ->firstOrFail();

                if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                    $danaAlokasi = Account::where(
                        'account_name',
                        'Dana Alokasi Pembiayaan Murabahah'
                    )->firstOrFail();

                    $danaAlokasiMasuk = JournalEntry::where(
                        'no_ref_account',
                        $danaAlokasi->no_ref_account
                    )
                    ->where('position', PositionEnum::DEBIT->value)
                    ->sum('nominal');

                    $danaAlokasiKeluar = JournalEntry::where(
                        'no_ref_account',
                        $danaAlokasi->no_ref_account
                    )
                    ->where('position', PositionEnum::CREDIT->value)
                    ->sum('nominal');

                    $saldoDanaAlokasi = $danaAlokasiMasuk - $danaAlokasiKeluar;

                    if ($saldoDanaAlokasi < $financing->predicted_cost_price) {
                        throw ValidationException::withMessages([
                            'status' =>
                                'Dana alokasi pembiayaan tidak mencukupi. Silakan lakukan alokasi dana terlebih dahulu.'
                        ]);
                    }
                }

            $financing->update([
                'status' => $validated['status'],
            ]);

            FinancingVerification::create([
                'financing_id' => $financing->id,
                'verified_by' => auth()->id(),
                'final_verification_status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'verified_at' => now(),
            ]);

            if ($validated['status'] === FinancingReqStatusEnum::APPROVED->value) {

                $pembiayaanDalamProses = Account::where(
                    'account_name',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $danaAlokasi = Account::where(
                    'account_name',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                app(JournalService::class)->create(
                    [
                        [
                            'account' => $pembiayaanDalamProses->no_ref_account,
                            'position' => PositionEnum::DEBIT->value,
                            'nominal' => $financing->predicted_cost_price,
                        ],
                        [
                            'account' => $danaAlokasi->no_ref_account,
                            'position' => PositionEnum::CREDIT->value,
                            'nominal' => $financing->predicted_cost_price,
                        ],
                    ],
                    now()->toDateString(),
                    auth()->id()
                );
            }

            return redirect()->route('admin.financings.index')->with('success', 'Keputusan validasi berhasil disimpan');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Error validating financing: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Gagal menyimpan keputusan validasi'
            ]);
        }
    }

    public function store(StoreFinancingRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus dalam status aktif']);
                }

                $hasEligibleSaving = SavingAccount::where('member_id', $user->member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());
            });

            return redirect()->route('admin.financings.index')
                ->with('success', 'Permohonan pembiayaan berhasil dikirim');

        } catch (Exception $e) {
            Log::error('Error storing financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function finalize(StoreFinancingRequest $request)
    {
        try {
            $financing = DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                if ($user->status !== UserStatusEnum::ACTIVE->value) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus dalam status aktif']);
                }

                $hasEligibleSaving = SavingAccount::where('member_id', $user->member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                if (!$hasEligibleSaving) {
                    throw ValidationException::withMessages(['member' => 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
                }

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $financing = $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());

                if ($request->hasFile('akad_document_file')) {
                    $financing->update([
                        'signed_akad_document' => $request->file('akad_document_file')->store('documents', 'public'),
                    ]);
                }

                if (isset($validated['financing']['tenor'])) {
                    $this->financingService->generateInstallments($financing);
                }

                $pembiayaanDalamProses = Account::where(
                    'account_name',
                    'Pembiayaan Dalam Proses'
                )->firstOrFail();

                $piutangMurabahah = Account::where(
                    'account_name',
                    'Piutang Murabahah'
                )->firstOrFail();

                $pendapatanMargin = Account::where(
                    'account_name',
                    'Pendapatan Margin Murabahah'
                )->firstOrFail();
                $danaAlokasi = Account::where(
                    'account_name',
                    'Dana Alokasi Pembiayaan Murabahah'
                )->firstOrFail();

                $kas = Account::where(
                    'account_name',
                    'Kas'
                )->firstOrFail();

                $costPrice = $financing->cost_price;
                $margin = $financing->margin_amount;
                $downPayment = $financing->down_payment ??0;

                // Kalo pembayaran pembiayaannya cicilan
                if ($financing->payment_method === FinancingPaymentMethodEnum::INSTALLMENT->value)
                {
                    $uangMukaMurabahah = Account::where(
                        'account_name',
                        'Uang Muka Murabahah'
                    )->firstOrFail();
                    $allocatedAmount = $financing->predicted_cost_price ?? 0;
                    $piutang = $costPrice;
                    $selisih = $allocatedAmount - $piutang;

                    if ($selisih > 0) {

                        app(JournalService::class)->create(
                            [
                                [
                                    'account' => $danaAlokasi->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $selisih,
                                ],
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $financing->predicted_cost_price,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } elseif ($selisih == 0){

                        app(JournalService::class)->create(
                            [
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $piutang,
                                ],
                                [
                                    'account' => $pembiayaanDalamProses->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $allocatedAmount,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    } else {
                        throw ValidationException::withMessages([
                            'cost_price' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }

                    if ($downPayment > 0)
                    {
                        app(JournalService::class)->create(
                            [
                                [
                                    'account' => $kas->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $downPayment,
                                ],
                                [
                                    'account' => $uangMukaMurabahah->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $downPayment,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );

                        app(JournalService::class)->create(
                            [
                                [
                                    'account' => $uangMukaMurabahah->no_ref_account,
                                    'position' => PositionEnum::DEBIT->value,
                                    'nominal' => $downPayment,
                                ],
                                [
                                    'account' => $piutangMurabahah->no_ref_account,
                                    'position' => PositionEnum::CREDIT->value,
                                    'nominal' => $downPayment,
                                ],
                            ],
                            now()->toDateString(),
                            auth()->id()
                        );
                    }
                }

                // Klo pembayaran pembiayaannya cash
                if (
                        $financing->payment_method === FinancingPaymentMethodEnum::CASH->value
                        && $downPayment > 0
                    ) {
                        throw ValidationException::withMessages([
                            'down_payment' => 'Pembayaran cash tidak boleh menggunakan uang muka.'
                        ]);
                    }

                if ($financing->payment_method === FinancingPaymentMethodEnum::CASH->value)
                {
                    $allocatedAmount = $financing->predicted_cost_price ?? 0;
                    $piutang = $costPrice;
                    $selisih = $allocatedAmount - $piutang;

                    if ($selisih > 0)
                    {
                        app(JournalService::class)->create(
                        [
                            [
                                'account' => $danaAlokasi->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $selisih,
                            ],
                            [
                                'account' => $kas->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'account' => $pembiayaanDalamProses->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'account' => $pendapatanMargin->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    } elseif ($selisih == 0)
                    {
                        app(JournalService::class)->create(
                        [
                            [
                                'account' => $kas->no_ref_account,
                                'position' => PositionEnum::DEBIT->value,
                                'nominal' => $piutang + $margin,
                            ],
                            [
                                'account' => $pembiayaanDalamProses->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $allocatedAmount,
                            ],
                            [
                                'account' => $pendapatanMargin->no_ref_account,
                                'position' => PositionEnum::CREDIT->value,
                                'nominal' => $margin,
                            ],
                        ],
                        now()->toDateString(),
                        auth()->id()
                        );
                    } else {
                        throw ValidationException::withMessages([
                            'cost_price' => 'Harga pokok aktual melebihi dana yang telah dialokasikan.'
                        ]);
                    }
                }
                return $financing;
            });
            return redirect()->route('admin.financings.index')
                ->with('success', 'Pembiayaan berhasil difinalisasi');
        } catch (Exception $e) {
            Log::error('Error storing financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
        }
    }

    public function saveDraft(StoreFinancingDraftRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $validated = $request->validated();
                $user = User::with('member.savingAccounts')
                    ->where('user_code', $validated['member']['user_code'])
                    ->firstOrFail();

                $this->financingService->syncMemberData($user, $validated['member'], $request);
                $this->financingService->syncFinancingData($user, $validated, $request, auth()->id());
            });

            return redirect()->route('admin.financings.index')
                ->with('success', 'Draft berhasil disimpan');

        } catch (Exception $e) {
            Log::error('Error saving draft: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan draft: ' . $e->getMessage()]);
        }
    }

    public function searchMembers(Request $request)
    {
        $query = $request->input('q');

        $members = Member::query()
            ->with(['user:id,user_code,name,email,nik,phone_number', 'memberDocs', 'financials', 'heirs', 'memberJobs', 'financings:id,status', 'savingAccounts:id,balance,created_at'])
            ->whereHas('user', function ($q) use ($query) {
                $q->whereHas('roles', fn($roleQ) => $roleQ->where('name', 'Anggota'))
                    ->where('status', UserStatusEnum::ACTIVE->value)
                    ->where(function ($searchQ) use ($query) {
                        $searchQ->where('name', 'ILIKE', "%{$query}%")
                            ->orWhere('user_code', 'ILIKE', "%{$query}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(function ($member) {
                $hasActiveFinancing = $member->financings?->whereIn(
                    'status',
                    [
                        FinancingReqStatusEnum::PENDING_REVIEW->value,
                        FinancingReqStatusEnum::REJECTED->value,
                        FinancingReqStatusEnum::APPROVED->value,
                        FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                        FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value,
                    ]
                )->isNotEmpty() ?? false;

                $member->is_have_no_obligation = !$hasActiveFinancing;

                $hasEligibleSaving = SavingAccount::where('member_id', $member->id)
                    ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                    ->where('created_at', '<=', now()->subMonth())
                    ->exists();

                $member->is_have_eligible_saving = $hasEligibleSaving;
                $member->family_card = $member->memberDocs->where('doc_name', 'kk')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'kk')->first()->doc_attachment) : null;
                $member->income_slip = $member->memberDocs->where('doc_name', 'slip_gaji')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'slip_gaji')->first()->doc_attachment) : null;
                $member->bank_book = $member->memberDocs->where('doc_name', 'buku_tabungan')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'buku_tabungan')->first()->doc_attachment) : null;

                return $member;
            });

        return response()->json(['members' => $members->values()]);
    }
    public function searchSuppliers(Request $request)
    {
        $query = $request->input('q');

        $suppliers = DB::table('suppliers')
            ->where('supplier_name', 'ILIKE', "%{$query}%")
            ->limit(5)
            ->get();

        return response()->json(['suppliers' => $suppliers]);
    }

    public function showRepayment(string $id, RepaymentService $repaymentService)
    {
        $financing = Financing::with([
            'member.user',
            'installment.payment',
            'financingItem.productType',
            'financingItem.supplier',
            'collateral'
        ])->where('status', '!=', FinancingReqStatusEnum::PAID->value)->findOrFail($id);

        $data = $repaymentService->calculateDetails($financing);

        $data['pengurus'] = auth()->user()->name;

        $unpaidInstallment = $financing->installment
            ->whereNotIn('status', [
                InstallmentPaymentScheduleStatusEnum::PAID->value,
                InstallmentPaymentScheduleStatusEnum::OVERDUE->value,
            ])
            ->sortBy('installment_no')
            ->first();

        $data['installment_id'] = $unpaidInstallment?->id;

        return inertia('Admin/Financing/Repayment/Create', [
            'data' => $data,
        ]);
    }

    public function storeRepayment(CreateRepaymentRequest $request, RepaymentService $service)
    {
        try {
            $transaction = $service->processRepayment($request->validated(), auth()->id());

            return inertia('Admin/Financing/Repayment/Result', [
                'data' => $transaction,
            ]);

        } catch (Exception $e) {
            Log::error('Error processing repayment: ' . $e->getMessage());
            return inertia('Admin/Financing/Repayment/Result', [
                'error' => 'Gagal memproses pembayaran: ' . $e->getMessage(),
            ]);
        }
    }

    public function createPayment(Financing $financing)
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

        $installment = Installment::where(
            'financing_id',
            $financing->id
        )
        ->whereNotIn('status', $paidStatuses)
        ->orderBy('installment_no')
        ->first();

        $nextInstallment = Installment::where(
            'financing_id',
            $financing->id
        )
        ->where('installment_no', '>', $installment?->installment_no)
        ->orderBy('installment_no')
        ->first();

        $paymentCount =
            InstallmentPaymentTransaction::where(
                'installment_id',
                $installment?->id
            )->count();

        $hargaJual =
            $financing->cost_price +
            $financing->margin_amount;

        $angsuranPerBulan = $installment?->amount ?? 0;

        $totalTerbayar =
            InstallmentPaymentTransaction::whereHas(
                'installment',
                function ($q) use ($financing) {
                    $q->where(
                        'financing_id',
                        $financing->id
                    );
                }
            )->sum('nominal');

        $sisa = $hargaJual - $totalTerbayar;

        $paymentCount = InstallmentPaymentTransaction::where(
            'installment_id', $installment?->id
        )->count();

        return Inertia::render(
            'Admin/Financing/Payment/Create',
            [
                'financing' => [
                    'id' => $financing->id,

                    'transaction_code' =>
                        $financing->financing_transaction_code,

                    'product_name' =>
                        $financing->financingItem?->name,

                    'product_type' =>
                        $financing->financingItem?->productType?->product_type_name,

                    'product_specification' =>
                        $financing->financingItem?->specification,

                    'color' => '-',

                    'qty' =>
                        $financing->financingItem?->qty,

                    'user' => [
                        'name' =>
                            $financing->member?->user?->name,

                        'user_code' =>
                            $financing->member?->user?->user_code,
                    ],

                    'installment_per_month' =>
                        $installment?->amount ?? 0,

                    'remaining_balance' =>
                        max($sisa, 0),

                    'next_installment_number' =>
                        $installment?->installment_no,

                    'current_due_date' =>
                        $installment?->due_date?->format('Y-m-d'),

                    'payment_count' => $paymentCount + 1,

                    'next_due_date' =>
                        $nextInstallment?->due_date?->format('Y-m-d'),

                    'financing_id' =>
                        $financing->id,

                    'installment_id' =>
                        $installment?->id,
                ],
            ]
        );
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'financing_id'   => 'required|exists:financings,id',
            'payment_method' => 'required|string',
            'nominal'        => 'required|numeric|min:1',
            'payment_date'   => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $financing = Financing::with([
                'member.user',
                'financingItem.productType',
                'installment',
            ])->findOrFail($validated['financing_id']);

            $paymentCount = InstallmentPaymentTransaction::where(
                'installment_id', $validated['installment_id']
            )->count();

            $payment = InstallmentPaymentTransaction::create([
                'installment_trans_code' => 'INS' . strtoupper(substr(uniqid(), -7)),
                'payment_method'         => $validated['payment_method'],
                'is_early_repayment'     => false,
                'nominal'                => $validated['nominal'],
                'payment_date'           => $validated['payment_date'],
                'installment_id'         => $validated['installment_id'],
                'updated_by'             => auth()->id(),
            ]);

            $marginPerMonth    = round($financing->margin_amount / $financing->tenor, 2);
            $principalPerMonth = round($validated['nominal'] - $marginPerMonth, 2);

            $kas               = Account::where('account_name', 'Kas')->firstOrFail();
            $piutangMurabahah  = Account::where('account_name', 'Piutang Murabahah')->firstOrFail();
            $pendapatanMargin  = Account::where('account_name', 'Pendapatan Margin Murabahah')->firstOrFail();

            app(JournalService::class)->create(
                [
                    ['account' => $kas->no_ref_account,              'position' => PositionEnum::DEBIT->value,  'nominal' => $validated['nominal']],
                    ['account' => $piutangMurabahah->no_ref_account, 'position' => PositionEnum::CREDIT->value, 'nominal' => $principalPerMonth],
                    ['account' => $pendapatanMargin->no_ref_account, 'position' => PositionEnum::CREDIT->value, 'nominal' => $marginPerMonth],
                ],
                now()->toDateString(),
                auth()->id()
            );

            $installment = Installment::findOrFail($validated['installment_id']);
            $paymentDate = \Carbon\Carbon::parse(
                $validated['payment_date']
            );

            $dueDate = $installment->due_date;

            $status =
                $paymentDate->startOfDay()
                    ->gt(
                        $dueDate->copy()->startOfDay()
                    )
                    ? InstallmentPaymentScheduleStatusEnum::OVERDUE->value
                    : InstallmentPaymentScheduleStatusEnum::PAID->value;

            $installment->update([
                'status' => $status,
            ]);

            $nextInstallment = Installment::where(
                'financing_id',
                $financing->id
            )
            ->where(
                'installment_no',
                '>',
                $installment->installment_no
            )
            ->orderBy('installment_no')
            ->first();

            $hargaJual    = $financing->cost_price + $financing->margin_amount;
            $totalTerbayar = InstallmentPaymentTransaction::whereHas('installment', function ($q) use ($financing) {
                $q->where('financing_id', $financing->id);
            })->sum('nominal');

            $sisa = $hargaJual - $totalTerbayar;

            if ($sisa <= 0) {
                $financing->update(['status' => 'Lunas']);
            }

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->withErrors(['message' => $th->getMessage()]);
        }

        $fileName = null;

        try {
            \Carbon\Carbon::setLocale('id');

            $logoPath = public_path('images/logo/logo-icon.svg');
            $logo = file_exists($logoPath)
                ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
                : '';

            $receipt = [
                'logo'          => $logo,
                'payment_method' => $payment->payment_method,
                'organization'  => [
                    'name'    => 'Koperasi Syariah Berkah',
                    'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                ],
                'petugas'             => auth()->user()->name,
                'tanggal_angsuran'    => \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d F Y'),
                'nomor_pembiayaan'    => $financing->financing_transaction_code,
                'no_anggota'          => $financing->member?->user?->user_code,
                'diterima_dari'       => $financing->member?->user?->name,
                'sejumlah_uang'       => $payment->nominal,
                'terbilang'           => ucfirst(\Riskihajar\Terbilang\Facades\Terbilang::make($payment->nominal)) . ' rupiah',
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
                'jatuh_tempo' =>
                    $nextInstallment
                        ? $nextInstallment->due_date
                            ->translatedFormat('d F Y')
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

        } catch (\Throwable $th) {
            \Log::error('PDF generation failed: ' . $th->getMessage());
        }

        return redirect("/admin/financings/show/{$financing->id}")
            ->with([
                'success' => 'Pembayaran berhasil diproses',
                'pdf_url' => $fileName ? asset('storage/' . $fileName) : null,
            ]);
    }

    public function reschedulePayment(Request $request, Financing $financing)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'due_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ]);

        try {

            $currentInstallment = Installment::findOrFail(
                $validated['installment_id']
            );

            $newDate = Carbon::parse($validated['due_date']);

            Installment::where(
                'financing_id',
                $financing->id
            )
            ->where(
                'installment_no',
                '>=',
                $currentInstallment->installment_no
            )
            ->orderBy('installment_no')
            ->get()
            ->each(function ($item, $index) use ($newDate) {

                $item->update([
                    'due_date' => $newDate
                        ->copy()
                        ->addMonths($index)
                ]);

            });

            return redirect("/admin/financings/show/{$financing->id}")
                ->with(
                    'success',
                    'Jadwal pembayaran berhasil diperbarui'
                );

        } catch (\Throwable $th) {

            return back()->withErrors([
                'message' => $th->getMessage(),
            ]);
        }
    }
}
