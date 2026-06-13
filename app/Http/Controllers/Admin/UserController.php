<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\HeirEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberAllocationRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Financing;
use App\Models\SavingAccount;
use App\Models\User;
use App\Services\Admin\PembiayaanService;
use App\Services\Admin\MemberAllocationService;
use App\Services\Admin\RegisterMemberService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/User/Create/Index', [
            'educationOptions' => $this->enumOptions(EducationEnum::cases()),
            'maritalStatusOptions' => $this->enumOptions(MaritalStatusEnum::cases()),
            'heirRelationshipOptions' => $this->enumOptions(HeirEnum::cases()),
        ]);
    }

    public function store(StoreMemberRequest $request, RegisterMemberService $registerMemberService)
    {
        $validated = $request->validated();

        try {
            $memberCredentials = $registerMemberService->register($validated, $request);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'member' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.users.index')->with([
            'success' => 'Anggota berhasil ditambahkan.',
            'member_credentials' => $memberCredentials,
        ]);
    }

    public function allocation(Request $request, MemberAllocationService $memberAllocationService)
    {
        return Inertia::render('Admin/User/Allocation/Index', $memberAllocationService->buildPageData($request));
    }

    public function storeAllocation(StoreMemberAllocationRequest $request, MemberAllocationService $memberAllocationService)
    {
        $memberAllocationService->allocate($request->validated());

        return redirect()->route('admin.users.allocation')->with('success', 'Alokasi anggota berhasil disimpan.');
    }

    private function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(fn($item) => [
                'value' => $item->value,
                'text' => $item->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');

        $allowedSorts = ['name', 'joined_date'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'joined_date';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

        $memberBaseQuery = User::with('member.savingAccounts')
            ->whereHas('member');

        if (auth()->user()->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $memberBaseQuery->whereHas('member', function ($q) {
                $q->where('pj_user_id', auth()->id());
            });
        }

        $verifiedMembersQuery = (clone $memberBaseQuery)
            ->whereNotNull('joined_date');

        $query = clone $memberBaseQuery;

        $query
            ->whereNotNull('joined_date')
            ->whereNotNull('user_code');

        $totalVerifiedMembers = $verifiedMembersQuery->count();

        $activeMembers = (clone $verifiedMembersQuery)
            ->where('status', UserStatusEnum::ACTIVE)
            ->count();

        $newThisMonth = (clone $verifiedMembersQuery)
            ->whereMonth('joined_date', now()->month)
            ->whereYear('joined_date', now()->year)
            ->count();

        if ($search) {
            $query->where(
                fn($q) =>
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('user_code', 'ILIKE', "%{$search}%")
                    ->orWhere('phone_number', 'ILIKE', "%{$search}%")
            );
        }

        if ($status) {
            $query->where('status', $status);
        }

        $members = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'no_anggota' => $user->user_code,
                'name' => $user->name,
                'joined_at' => $user->joined_date
                    ? \Carbon\Carbon::parse($user->joined_date)->format('d/m/Y')
                    : null,
                'phone' => $user->phone_number,
                'status' => $user->status,
                'total_simpanan' => 'Rp ' . number_format(
                    DB::table('saving_accounts')->where('member_id', $user->member?->id)->sum('balance') ?? 0,
                    0,
                    ',',
                    '.'
                ),
                'avatar' => $user->profile_picture
                    ? asset('storage/' . $user->profile_picture)
                    : null,
            ]);

        return Inertia::render('Admin/User/List', [
            'members' => $members,
            'filters' => $request->only([
                'search',
                'status',
                'per_page',
                'sort_by',
                'sort_dir'
            ]),
            'summary' => [
                'total_verified' => $totalVerifiedMembers,

                'active' => $activeMembers,
                'new_this_month' => $newThisMonth,

                'active_percent' => $totalVerifiedMembers > 0
                    ? round(($activeMembers / $totalVerifiedMembers) * 100)
                    : 0,

                'new_percent' => $totalVerifiedMembers > 0
                    ? round(($newThisMonth / $totalVerifiedMembers) * 100)
                    : 0,
            ],
            'statuses' => array_column(UserStatusEnum::cases(), 'value'),
            'can' => [
                'tambah_anggota' => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
                'edit_anggota'   => Auth::user()->hasRole(UserRoleEnum::SEKRETARIS->value),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, PembiayaanService $service)
    {
        $ktpDoc = null;
        $kkDoc = null;

        $user = User::with([
            'member.memberDocs',
            'roles',
            'member.savingAccounts.transactions' => fn($q) => $q->orderBy('transaction_date', 'desc'),
            'member.savingAccounts',
            'member.heirs',
            'member.financings.installment.payment',
            'member.financings.financingItem',
        ])->findOrFail($id);

        $user->profile_picture = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        if ($user->member) {
            $ktpDoc = $user->member->memberDocs->where('doc_name', 'ktp')->first();
            $kkDoc = $user->member->memberDocs->where('doc_name', 'kartu_keluarga')->first();

            if ($user->member->financings) {
                $user->member->financings->each(function ($financing) use ($service) {
                    $service->computeFinancingSummary($financing);
                    $nextInstallment = $financing->installment
                    ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
                    ->sortBy('due_date')
                    ->first();

                $financing->setAttribute('next_due_date', $nextInstallment?->due_date);
                });
            }
        }

        return inertia('Admin/User/Show', [
            'user' => $user,
            'ktp_photo' => $ktpDoc?->doc_attachment ? asset('storage/' . $ktpDoc->doc_attachment) : null,
            'kk_photo' => $kkDoc?->doc_attachment ? asset('storage/' . $kkDoc->doc_attachment) : null,
        ]);
    }

    public function edit(string $id)
    {
        $user = User::with(['member', 'member.memberDocs' => function ($query) {
            $query->whereIn('doc_name', ['ktp', 'kk']);
        }, 'member.heirs'])->findOrFail($id);

        $user->kk = $user->member?->memberDocs?->firstWhere('doc_name', 'kartu_keluarga')?->doc_attachment
            ? asset('storage/' . $user->member->memberDocs->firstWhere('doc_name', 'kartu_keluarga')->doc_attachment)
            : null;

        $user->ktp = $user->member?->memberDocs?->firstWhere('doc_name', 'ktp')?->doc_attachment
            ? asset('storage/' . $user->member->memberDocs->firstWhere('doc_name', 'ktp')->doc_attachment)
            : null;

        return inertia('Admin/User/Edit', [
            'data' => $user,
            'opsiPendidikan' => $this->enumOptions(EducationEnum::cases()),
            'opsiStatusPerkawinan' => $this->enumOptions(MaritalStatusEnum::cases()),
            'opsiHubunganKeluarga' => $this->enumOptions(HeirEnum::cases()),
        ]);
    }

    public function update(UpdateMemberRequest $request, string $id)
    {
        $validated = $request->validated();

        $user = User::with('member.memberDocs', 'member.heirs')->findOrFail($id);

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'name' => $validated['name'] ?? $user->name,
                    'nik' => $validated['nik'] ?? $user->nik,
                    'email' => $validated['email'] ?? $user->email,
                    'phone_number' => $validated['phone_number'] ?? $user->phone_number,
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

                if (isset($validated['heirs'])) {
                    $user->member->heirs()->delete();

                    foreach ($validated['heirs'] as $heirData) {
                        $user->member->heirs()->create($heirData);
                    }
                }

                if (isset($validated['ktp_file'])) {
                    $user->member->memberDocs()->firstOrCreate([
                        'doc_name' => 'ktp',
                        'doc_attachment' => $validated['ktp_file']->store('member_docs', 'public'),
                        'member_id' => $user->member->id,
                    ]);
                }

                if (isset($validated['kk_file'])) {
                    $user->member->memberDocs()->firstOrCreate([
                        'doc_name' => 'kk',
                        'doc_attachment' => $validated['kk_file']->store('member_docs', 'public'),
                        'member_id' => $user->member->id
                    ]);
                }
            });

            return redirect()->route('admin.users.index');
        } catch (Exception $e) {
            return back()->withErrors([
                'member' => $e->getMessage(),
            ]);
        }
    }

    public function getMutasi($accountId)
    {
        $account = SavingAccount::with([
            'transactions' => fn($q) => $q->latest('transaction_date')
        ])->findOrFail($accountId);

        return response()->json($account->transactions);
    }

    public function getRiwayat($financingId)
    {
        $financing = Financing::with([
            'installment' => fn($q) => $q->orderBy('installment_no', 'asc'),
            'installment.payment'
        ])->findOrFail($financingId);

        if ($financing->installment->isEmpty()) {
            return response()->json([]);
        }

        return response()->json($financing->installment);
    }

    public function verificationDetail(User $user)
    {
        $user->load('userDocs');

        $photoUrl = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        $idCard = $user->userDocs
            ->firstWhere('name', 'ktp');
        $idCardUrl = $idCard?->attachment ? asset('storage/' . $idCard->attachment) : null;

        return Inertia::render('Admin/User/Verification/Show', [
            'member' => [
                'id' => $user->id,
                'user_code' => $user->user_code,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'photo_url' => $photoUrl,
                'id_card_url' => $idCardUrl,
            ],
        ]);
    }

    public function updateStatusToInactive(string $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'status' => UserStatusEnum::INACTIVE,
        ]);

        return redirect()->back();
    }
}
