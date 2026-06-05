<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConditionEnum;
use App\Enums\EducationEnum;
use App\Enums\FinancialCostEnum;
use App\Enums\FinancialIncomeEnum;
use App\Enums\FinancingReqStatusEnum;
use App\Enums\HeirEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\PositionEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRepaymentRequest;
use App\Http\Requests\StoreFinancingRequest;
use App\Models\Financial;
use App\Models\Financing;
use App\Models\FinancingItem;
use App\Models\FinancingVerification;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\SavingAccount;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wakalah;
use App\Models\MemberDoc;
use App\Models\InstallmentPaymentTransaction;
use App\Services\Admin\RepaymentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FinancingController extends Controller
{
    private function baseQuery(Request $request)
    {
        $verifier = auth()->user();
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');

        return Financing::with([
            'member.user' => function ($query) {
                $query->select('id', 'name', 'user_code');
            },
            'installment' => function ($query) {
                $query->withCount('payment');
            },
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
                    'akad_date' => $f->akad_date,
                    'user' => $f->member->user
                        ? ($f->member->user->user_code . ' - ' . $f->member->user->name)
                        : '-',
                    'tenor_left' => $f->installment?->payment_schedules_count ?? 0,
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
        $modalCredit = JournalEntry::
            with([
                'account' => function ($q) {
                    $q->where('account_name', 'Modal Murabahah');
                }
            ])
            ->where('position', PositionEnum::CREDIT->value)
            ->sum('nominal');

        $modalDebit = JournalEntry::
            with([
                'account' => function ($q) {
                    $q->where('account_name', 'Modal Murabahah');
                }
            ])
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

        if ($installment && $installment->payment?->count() > 0) {
            $total_paid = 0;

            foreach ($installment->payment as $payment) {
                $total_paid += $payment->nominal ?? 0;
            }

            $financing->remaining_balance = $financing->total_price - $total_paid;
            $financing->total_monthly_payment = $financing->margin_amount + $financing->cost_price - $financing->down_payment / $financing->installment->tenor;
            $financing->total_paid = $total_paid;

            if ($financing->installment?->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->installment->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
        } else {
            $financing->total_paid = 0;
            if ($installment && $financing->installment?->tenor) {
                $financing->installment_per_month = ($financing->total_price) / $financing->installment->tenor;
            } else {
                $financing->installment_per_month = 0;
            }
            $financing->remaining_balance = $financing->total_price;
        }

        if ($installment && $financing->akad_date) {
            $paid_count = $installment->payment ? $installment->payment->count() : 0;
            if ($paid_count < $installment->tenor) {
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
                $member->family_card = $member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'kartu_keluarga')->first()->doc_attachment) : null;
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
                    'nominal_wakalah' => $financing->wakalah?->nominal_wakalah,
                    'akad_date' => $financing->akad_date,
                    'status' => $financing->status,
                    'tenor' => $financing->installment?->tenor,
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
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
            ])
            ->first();

        if (!$financing) {
            return redirect()->route('admin.financing.index')->withErrors(['error' => 'Data pembiayaan tidak ditemukan atau tidak dalam status yang valid untuk divalidasi']);
        }

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
                ],
                'collateral' => [
                    'collateral_type' => $financing->collateral?->collateral_type,
                    'owner_name' => $financing->collateral?->owner_name,
                    'estimated_market_value' => $financing->collateral?->estimated_market_value,
                    'collateral_location' => $financing->collateral?->collateral_location,
                ],
                'documents' => [
                    'family_card' => $this->getDocumentUrl($financing->member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment),
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

            return redirect()->route('admin.financing.index')->with('success', 'Keputusan validasi berhasil disimpan');
        } catch (Exception $e) {
            Log::error('Error validating financing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan keputusan validasi']);
        }
    }


    public function store(StoreFinancingRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $user = User::with('member.savingAccounts')->where('user_code', $validated['member']['user_code'])->firstOrFail();
            $verifier = auth()->user();

            // VALIDASI 1: PEMOHON HARUS DALAM STATUS AKTIF
            if ($user->status !== UserStatusEnum::ACTIVE->value) {
                throw ValidationException::withMessages(['member' => 'Pemohon harus dalam status aktif']);
            }

            // VALIDASI 2: PEMOHON HARUS MEMILIKI SIMPANAN AKTIF SATU BULAN
            $hasEligibleSaving = SavingAccount::where('member_id', $user->member->id)
                ->where('saving_type', SavingTypeEnum::TABUNGAN_ANGGOTA->value)
                ->where('created_at', '<=', now()->subMonth())
                ->first();

            if (!$hasEligibleSaving) {
                throw ValidationException::withMessages(['member' => 'Pemohon harus memiliki simpanan aktif minimal satu bulan']);
            }

            Log::info('storingfinancingdata', ['validated' => $validated, 'user' => $user->id]);

            // Update user
            $user->update([
                'name' => $validated['member']['name'],
                'nik' => $validated['member']['nik'],
                'email' => $validated['member']['email'] ?? $user->email,
                'phone_number' => $validated['member']['phone_number'] ?? $user->phone_number,
            ]);

            // Update member
            $user->member->update([
                'gender' => $validated['member']['gender'] ?? $user->member->gender,
                'birth_place' => $validated['member']['birth_place'] ?? $user->member->birth_place,
                'birth_date' => $validated['member']['birth_date'] ?? $user->member->birth_date,
                'last_education' => $validated['member']['last_education'] ?? $user->member->last_education,
                'domicile_address' => $validated['member']['domicile_address'] ?? $user->member->domicile_address,
                'residential_address' => $validated['member']['residential_address'] ?? $user->member->residential_address,
                'marital_status' => $validated['member']['marital_status'] ?? $user->member->marital_status,
                'dependents' => $validated['member']['dependents'] ?? $user->member->dependents,
            ]);

            // Sync heirs
            $user->member->heirs()->delete();
            if (!empty($validated['member']['heirs'] ?? [])) {
                $user->member->heirs()->createMany($validated['member']['heirs']);
            }

            // Sync documents
            $documents = [
                'slip_gaji' => 'income_slip_file',
                'buku_tabungan' => 'bank_book_file',
            ];
            foreach ($documents as $docName => $fileField) {
                if ($request->hasFile($fileField)) {
                    $user->member->memberDocs()->updateOrCreate(
                        ['doc_name' => $docName],
                        ['doc_attachment' => $request->file($fileField)->store('documents', 'public')]
                    );
                }
            }

            // Sync financials
            $user->member->financials()->delete();
            Financial::create(
                [
                    'member_id' => $user->member->id,
                    'gaji_pokok_amount' => $validated['member']['gaji_pokok_amount'] ?? 0,
                    'penghasilan_usaha_amount' => $validated['member']['penghasilan_usaha_amount'] ?? 0,
                    'penghasilan_pasangan_amount' => $validated['member']['penghasilan_pasangan_amount'] ?? 0,
                    'penghasilan_lainnya_amount' => $validated['member']['penghasilan_lainnya_amount'] ?? 0,
                    'biaya_hidup_keluarga_amount' => $validated['member']['biaya_hidup_keluarga_amount'] ?? 0,
                    'biaya_pendidikan_amount' => $validated['member']['biaya_pendidikan_amount'] ?? 0,
                    'jumlah_cicilan_amount' => $validated['member']['jumlah_cicilan_amount'] ?? 0,
                    'jumlah_tanggungan_amount' => $validated['member']['jumlah_tanggungan_amount'] ?? 0,
                    'jumlah_biaya_lainnya_amount' => $validated['member']['jumlah_biaya_lainnya_amount'] ?? 0,
                ]
            );

            // Sync job
            $user->member->memberJobs()->delete();
            if (isset($validated['member']['job_title'])) {
                $user->member->memberJobs()->create([
                    'employment_status' => $validated['member']['employment_status'] ?? null,
                    'job_title' => $validated['member']['job_title'] ?? null,
                    'company_or_business_name' => $validated['member']['company_or_business_name'] ?? null,
                    'business_field' => $validated['member']['business_field'] ?? null,
                    'tenure_year' => $validated['member']['tenure_year'] ?? null,
                    'workplace_address' => $validated['member']['workplace_address'] ?? null,
                    'workplace_contact' => $validated['member']['workplace_contact'] ?? null,
                ]);
            }

            if (isset($validated['financing']['name'])) {
                $financing = Financing::updateOrCreate(
                    ['member_id' => $user->member->id],
                    [
                        'down_payment' => $validated['financing']['down_payment'] ?? 0,
                        'akad_date' => $validated['financing']['akad_date'] ?? null,
                        'cost_price' => $validated['financing']['cost_price'] ?? null,
                        'margin_amount' => $validated['financing']['margin_amount'] ?? null,
                        'payment_method' => $validated['financing']['payment_method'] ?? null,
                        'updated_by' => $verifier->id,
                        'status' => $validated['financing']['status'] ?? FinancingReqStatusEnum::WAITING_DOCUMENTS->value,
                    ]
                );

                if ($financing->status === FinancingReqStatusEnum::PENDING_REVIEW->value) {
                    $financing->update(['requested_date' => now()]);
                }

                if (isset($validated['supplier']['supplier_name'])) {
                    $supplier = Supplier::updateOrCreate(
                        ['supplier_name' => $validated['supplier']['supplier_name']],
                        [
                            'address' => $validated['supplier']['address'] ?? null,
                        ]
                    );
                }

                $financingItem = FinancingItem::updateOrCreate(
                    ['financing_id' => $financing->id],
                    [
                        'name' => $validated['financing']['name'] ?? null,
                        'specification' => $validated['financing']['specification'] ?? null,
                        'qty' => $validated['financing']['qty'] ?? null,
                        'condition' => $validated['financing']['condition'] ?? null,
                        'price_per_unit' => $validated['financing']['price_per_unit'] ?? null,
                        'product_type_id' => $validated['financing']['product_type_id'] ?? null,
                        'supplier_id' => $supplier?->id ?? null,
                    ]
                );

                if (isset($validated['financing']['nominal_wakalah'])) {
                    Log::info('Updating wakalah', [
                        'financing_id' => $financing->id,
                        'nominal_wakalah' => $validated['financing']['nominal_wakalah'] ?? null,
                        'akad_date' => $validated['financing']['akad_wakalah_date'] ?? null,
                    ]);
                    $wakalah = Wakalah::updateOrCreate(
                        ['financing_id' => $financing->id],
                        [
                            'nominal_wakalah' => $validated['financing']['nominal_wakalah'] ?? null,
                            'akad_date' => $validated['financing']['akad_date'] ?? null,
                        ]
                    );

                    if ($request->hasFile('akad_wakalah_file')) {
                        $wakalah->update([
                            'signed_akad_document' => $request->file('akad_wakalah_file')->store('documents', 'public'),
                        ]);
                    }
                }

                if (isset($validated['collateral']['collateral_type'])) {
                    $financing->collateral()->updateOrCreate(
                        ['financing_id' => $financing->id],
                        [
                            'collateral_type' => $validated['collateral']['collateral_type'] ?? null,
                            'owner_name' => $validated['collateral']['owner_name'] ?? null,
                            'estimated_market_value' => $validated['collateral']['estimated_market_value'] ?? null,
                            'collateral_location' => $validated['collateral']['collateral_location'] ?? null,
                        ]
                    );
                }

                // FILE UPLOADS
                if($request->hasFile('purchase_receipt_file')) {
                    $financingItem->update([
                        'purchase_receipt' => $request->file('purchase_receipt_file')->store('documents', 'public'),
                    ]);
                }

                if ($request->hasFile('akad_document_file')) {
                    $financing->update([
                        'signed_akad_document' => $request->file('akad_document_file')->store('documents', 'public'),
                    ]);
                }

                if ($request->hasFile('akad_wakalah_file')) {
                    $user->member->memberDocs()->updateOrCreate(
                        ['doc_name' => 'akad_wakalah'],
                        ['doc_attachment' => $request->file('akad_wakalah_file')->store('documents', 'public')]
                    );
                }

                // IF INSTALLMENT
                if (isset($validated['financing']['tenor'])) {
                    Installment::create([
                        'financing_id' => $financing->id,
                        'tenor' => $validated['financing']['tenor'],
                        'due_day' => Carbon::parse($validated['financing']['akad_date'])->day,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.financings.index')->with('success', 'Permohonan pembiayaan berhasil disimpan');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing draft: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal menyimpan permohonan: ' . $e->getMessage()]);
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
                $member->family_card = $member->memberDocs->where('doc_name', 'kartu_keluarga')->first()?->doc_attachment ? asset('storage/' . $member->memberDocs->where('doc_name', 'kartu_keluarga')->first()->doc_attachment) : null;
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

        $installment = $financing->installment()
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
            InstallmentPaymentTransaction::where(
                'installment_id',
                $installment?->id
            )->sum('nominal');

        $sisa = $hargaJual - $totalTerbayar;

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

                    'brand' =>
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

                    'next_due_date' =>
                        $installment?->due_date,

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
            'financing_id' => 'required|exists:financings,id',
            'payment_method' => 'required|string',
            'nominal' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {

            $financing = Financing::with([
                'member.user',
                'financingItem.productType',
                'installment',
            ])->findOrFail($validated['financing_id']);

            $paymentCount = InstallmentPaymentTransaction::where(
                'installment_id',
                $validated['installment_id']
            )->count();

            $payment = InstallmentPaymentTransaction::create([
                // 'installment_trans_code' =>
                //     'INS-' . strtoupper(uniqid()),
                'installment_trans_code' =>
                    'INS' . strtoupper(substr(uniqid(), -7)),
                'payment_method' => $validated['payment_method'],
                'is_early_repayment' => false,
                'nominal' => $validated['nominal'],
                'payment_date' => $validated['payment_date'],
                'installment_id' => $validated['installment_id'],
                'updated_by' => auth()->id(),
            ]);

            $installment = Installment::findOrFail(
                $validated['installment_id']
            );

            $installment->update([
                'status' => InstallmentPaymentScheduleStatusEnum::PAID->value,
            ]);

            $hargaJual =
                $financing->cost_price +
                $financing->margin_amount;

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

            if ($sisa <= 0) {
                $financing->update([
                    'status' => 'Lunas',
                ]);
            }

            \Carbon\Carbon::setLocale('id');

            $logoPath = public_path(
                'images/logo/logo-icon.svg'
            );

            $logo = '';

            if (file_exists($logoPath)) {

                $logo =
                    'data:image/svg+xml;base64,' .
                    base64_encode(file_get_contents($logoPath));
            }

            $receipt = [
                'logo' => $logo,

                'payment_method' => $payment->payment_method,

                'organization' => [
                    'name' => 'Koperasi Syariah Berkah',
                    'address' => 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
                ],

                'petugas' => auth()->user()->name,

                'tanggal_angsuran' =>
                    \Carbon\Carbon::parse(
                        $payment->payment_date
                    )->translatedFormat('d F Y'),

                'nomor_pembiayaan' =>
                    $financing->financing_transaction_code,

                'no_anggota' =>
                    $financing->member?->user?->user_code,

                'diterima_dari' =>
                    $financing->member?->user?->name,

                'sejumlah_uang' =>
                    $payment->nominal,

                'terbilang' =>
                    ucfirst(
                        \Riskihajar\Terbilang\Facades\Terbilang::make(
                            $payment->nominal
                        )
                    ) . ' rupiah',

                'items' => [
                    [
                        'no' => 1,
                        'keterangan' =>
                            'Angsuran ke ' . ($paymentCount + 1),

                        'jumlah' =>
                            $payment->nominal,
                    ],
                ],

                'harga_perolehan' =>
                    $financing->cost_price,

                'margin' =>
                    $financing->margin_amount,

                'harga_jual' =>
                    $hargaJual,

                'total_angsuran' =>
                    $payment->nominal,

                'sisa_hutang' =>
                    max($sisa, 0),

                'status' =>
                    max($sisa, 0) <= 0
                        ? 'Lunas'
                        : 'Belum Lunas',

                'jatuh_tempo' =>
                    now()->addMonth()->translatedFormat('d F Y'),

                'catatan' =>
                    'Dasar akad yang digunakan adalah akad murabahah yang merupakan kontrak jual beli syariah.',

                'tanggal_cetak' =>
                    now()->translatedFormat('d F Y'),
            ];

            // Buat generate PDF kwitansi pembayaran
            $pdf = Pdf::loadView(
                'exports.financing_payment_receipt',
                [
                    'receipt' => $receipt,
                ]
            )->setPaper('a5', 'landscape');

            $pdf->setOptions([
                'isRemoteEnabled' => true,
            ]);

            $fileName =
                'receipts/' .
                $financing->member->id .
                '/receipt-' .
                time() .
                '.pdf';

            Storage::disk('public')->put(
                $fileName,
                $pdf->output()
            );

            // Buat simpan ke tabel member_docs sebagai bukti pembayaran
            MemberDoc::create([
                'member_id' => $financing->member_id,
                'doc_name' =>
                    'Kwitansi Pembayaran ' .
                    $payment->installment_trans_code,
                'doc_attachment' => $fileName,
            ]);

            // buat update ke tabel installment_payment_transactions (nyimpen path file kwitansi)
            $payment->update([
                'installment_payment_receipt' => $fileName,
            ]);

            DB::commit();

            return redirect("/admin/financings/show/{$financing->id}")
                ->with([
                    'success' => 'Pembayaran berhasil diproses',
                    'pdf_url' => asset('storage/' . $fileName),
                ]);

        } catch (\Throwable $th) {

            DB::rollBack();

            return back()->withErrors([
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function reschedulePayment(Request $request, Financing $financing)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:installments,id',
            'due_date' => 'required|date',
        ]);

        try {

            $installment = Installment::findOrFail(
                $validated['installment_id']
            );

            $installment->update([
                'due_date' => $validated['due_date'],
            ]);

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
