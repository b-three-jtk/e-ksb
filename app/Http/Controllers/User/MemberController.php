<?php

namespace App\Http\Controllers\User;

use App\Enums\FinancingReqStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Enums\EducationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateResignRequest;
use App\Models\Financing;
use App\Models\MemberDoc;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\User\UserProfileService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user()->load('member');

        $totalSaving = DB::table('saving_accounts')
            ->where('member_id', $user->member->id)
            ->sum('balance');

        $totalInstallment = DB::table('get_total_financing')->where('member_id', $user->member->id)->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->sum('total_financing');

        $ledger = SavingTransaction::whereHas(
            'savingAccount.member',
            fn($q) => $q->where('member_id', $user->member->id)
        )
            ->with('savingAccount')
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(function ($trx) {
                return [
                    'date' => Carbon::parse($trx->transaction_date)->format('d/m/Y'),
                    'product' => $trx->savingAccount->saving_type,
                    'type' => $trx->transaction_type,
                    'amount' => 'Rp ' . number_format($trx->saving_amount, 0, ',', '.'),
                ];
            });

        $activeMurabahahCount = Financing::where('member_id', $user->member->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->count();

        return inertia('User/Dashboard', [
            'summary' => [
                'total_saving' => $totalSaving,
                'total_installment' => $totalInstallment,
                'murabahah_count' => $activeMurabahahCount,
            ],
            'ledger' => $ledger,
        ]);
    }

    public function createResign()
    {
        $user = auth()->user()->load('member');

        $hasExistingResign = $user->member->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        Log::info('User ' . $user->id . ' is accessing resignation form with existing resign: ' . ($hasExistingResign ? 'yes' : 'no')) ;

        $totalSaving = SavingTransaction::whereHas(
            'savingAccount',
            fn($q) =>
            $q->where('member_id', $user->member->id)
        )
            ->sum(DB::raw("
                CASE
                    WHEN transaction_type = 'Penyetoran' THEN saving_amount
                    WHEN transaction_type = 'Penarikan' THEN -saving_amount
                END
            "));

        $totalObligation = DB::table('get_total_financing')->where('member_id', $user->member->id)->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)->sum('total_financing');

        return inertia('User/Resign/Create', [
            'member' => [
                ...$user->toArray(),
                'total_saving' => $totalSaving,
                'total_obligation' => $totalObligation,
            ],
            'has_existing_resign' => $hasExistingResign,
        ]);
    }

    public function storeResign(CreateResignRequest $request)
    {
        $user = auth()->user()->load('member');

        $hasExistingResign = $user->member->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        Log::info('User ' . $user->id . ' is trying to submit resignation with existing resign: ' . ($hasExistingResign ? 'yes' : 'no')) ;

        if ($hasExistingResign) {
            return back()->withErrors([
                'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.',
            ]);
        }

        if ($user->member->status !== MemberStatusEnum::ACTIVE->value) {
            return back()->withErrors([
                'resign' => 'Status anggota tidak valid untuk pengajuan pengunduran diri.',
            ]);
        }

        $hasObligation = Financing::where('member_id', $user->member->id)
            ->where('status', FinancingReqStatusEnum::ACTIVE_INSTALLMENTS->value)
            ->exists();

        if ($hasObligation) {
            return back()->withErrors([
                'resign' => 'Anda masih memiliki kewajiban finansial yang belum dilunasi. Silakan selesaikan kewajiban tersebut sebelum mengajukan pengunduran diri.',
            ]);
        }

        $data = $request->validated();

        $path = $data['document']->store('resign_docs', 'public');

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'document' => 'Gagal menyimpan dokumen. Silakan coba lagi.',
            ]);
        }

        DB::beginTransaction();
        try {
            MemberDoc::create([
                'doc_name' => 'Dokumen Pengunduran Diri',
                'doc_attachment' => $path,
                'member_id' => $user->member->id,
            ]);

            $member = $user->member;
            $member->status = MemberStatusEnum::RESIGNED_REQUESTED->value;
            $member->save();

            DB::commit();

            return redirect()
                ->route('user.userDashboard')
                ->with('success', 'Permohonan pengunduran diri berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'resign' => 'Terjadi kesalahan saat mengajukan pengunduran diri. Silakan coba lagi.',
            ]);
        }
    }

    // Profile management methods
    public function __construct(private UserProfileService $userProfileService)
    {
    }

    public function profileShow()
    {
        $user = auth()->user();

        return Inertia::render('User/Profile/Show', [
            'user' => $this->userProfileService->buildProfilePayload($user),
        ]);
    }

    public function profileEdit()
    {
        $user = auth()->user();
        $educationOptions = array_column(EducationEnum::cases(), 'value');

        return Inertia::render('User/Profile/Edit', [
            'user' => $this->userProfileService->buildProfilePayload($user),
            'educationOptions' => $educationOptions,
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => [
                'required',
                'string',
                'size:16',
                Rule::unique('users', 'nik')->ignore($user->id, 'id'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id, 'id'),
            ],
            'phone_number' => 'nullable|string|max:20',
            'last_education' => 'nullable|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'residential_address' => 'nullable|string|max:1000',
        ]);

        $this->userProfileService->updateProfile($user, $validated);

        return redirect()->route('user.profile.show');
    }

    public function updateProfilePicture(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $tmpPath = $request->file('profile_picture')->getPathname();
        if (!@getimagesize($tmpPath)) {
            return back()->withErrors(['profile_picture' => 'File tidak valid sebagai gambar.']);
        }

        $this->userProfileService->updateProfilePicture($user, $request->file('profile_picture'));

        return redirect()->back();
    }

    public function deleteProfilePicture()
    {
        $user = auth()->user();

        $this->userProfileService->deleteProfilePicture($user);

        return redirect()->back();
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Password saat ini tidak sesuai.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.required' => 'Password baru harus diisi.',
            'password.min' => 'Password harus minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf besar dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai dengan password baru.',
        ]);

        $this->userProfileService->updatePassword($user, $validated['password']);

        return redirect()->back()->with('success', 'Password berhasil diubah');
    }
}
