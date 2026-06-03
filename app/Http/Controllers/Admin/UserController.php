<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\HeirEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberAllocationRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Models\Financing;
use App\Models\SavingAccount;
use App\Models\User;
use App\Services\Admin\MemberAllocationService;
use App\Services\Admin\RegisterMemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use RuntimeException;

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

        $query = User::with('member.savingAccounts')
            ->whereHas('member')
            ->whereNotNull('joined_date')
            ->whereNotNull('user_code');

        $memberBaseQuery = User::with('member.savingAccounts')
            ->whereHas('member');

        $verifiedMembersQuery = (clone $memberBaseQuery)
            ->whereNotNull('joined_date');

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
            'statuses' => array_column(UserStatusEnum::cases(), 'value')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ktpDoc = null;
        $kkDoc = null;

        $user = User::with([
            'member.memberDocs',
            'roles',
            'member.savingAccounts.transactions',
            'member.savingAccounts',
            'member.heirs',
            'member.financings.installment.payment',
            'member.financings.financingItem',
        ])->findOrFail($id);

        $user->profile_picture = $user->profile_picture ? asset('storage/' . $user->profile_picture) : null;
        if ($user->member) {
            $ktpDoc = $user->member->memberDocs?->firstWhere('name', 'ktp');
            $kkDoc = $user->member->memberDocs?->firstWhere('name', 'kk');

            if ($user->member->financings) {
                $user->member->financings->each(function ($financing) {
                    $financing->installment_payment_paid_count = $financing->installment->payment
                        ->count();
                    $financing->next_payment = $financing->installment
                        ->sortBy('payment_date')
                        ->first();
                    $financing->total_price = $financing->cost_price + $financing->margin_amount - $financing->down_payment;
                    $financing->monthly_installment = $financing->installment->tenor > 0
                        ? ($financing->cost_price + $financing->margin_amount) / $financing->installment->tenor
                        : null;
                    $financing->remaining_total = $financing->total_price - ($financing->installment_payment_paid_count * ($financing->monthly_installment ?? 0));
                });
            }
        }

        return inertia('Admin/User/Show', [
            'user' => $user,
            'ktp_photo' => $ktpDoc?->attachment ? asset('storage/' . $ktpDoc->attachment) : null,
            'kk_photo' => $kkDoc?->attachment ? asset('storage/' . $kkDoc->attachment) : null,
        ]);
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
            'installment.paymentSchedules.payment' => fn($q) => $q->latest('payment_date')
        ])->findOrFail($financingId);

        return response()->json($financing->installment->paymentSchedules);
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
