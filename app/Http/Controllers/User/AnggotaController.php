<?php

namespace App\Http\Controllers\User;

use App\Enums\MemberStatusEnum;
use App\Enums\EducationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateResignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ProfilPenggunaService;
use App\Services\User\DasborService;
use App\Services\User\PengunduranDiriService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnggotaController extends Controller
{
    /**
     * Create a new controller instance.
     */

    public function __construct(
        protected DasborService $dasborService,
        protected ProfilPenggunaService $profilPenggunaService,
        protected PengunduranDiriService $pengunduranDiriService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user()->load('member');

        return inertia('User/Dashboard', [
            'summary' => $this->dasborService->getSummary($user->member->id, $user->id),
            'tabungan'  => $this->dasborService->getTabungan($user->member->id),
        ]);
    }

    public function createResign()
    {
        $user = auth()->user()->load('member');

        $hasExistingResign = $user->member->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        $resignData = $this->pengunduranDiriService->getResignData($user->member->id);

        return inertia('User/Resign/Create', [
            'member' => [
                ...$user->toArray(),
                'total_saving'     => $resignData['total_saving'],
                'total_obligation' => $resignData['total_obligation'],
            ],
            'has_existing_resign' => $hasExistingResign,
            'member_status'       => $user->member->status,
        ]);
    }

    public function storeResign(CreateResignRequest $request)
    {
        $user = auth()->user()->load('member');

        $hasExistingResign = $user->member->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        Log::info('User ' . $user->id . ' is trying to submit resignation with existing resign: ' . ($hasExistingResign ? 'yes' : 'no'));

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

        $totalObligation = $this->pengunduranDiriService->getTotalObligation($user->member->id);

        if ($totalObligation > 0) {
            return back()->withErrors([
                'resign' => 'Anda masih memiliki kewajiban finansial yang belum dilunasi. Silakan selesaikan kewajiban tersebut sebelum mengajukan pengunduran diri.',
            ]);
        }

        $data = $request->validated();

        try {
            $this->pengunduranDiriService->submitResign($data['document'], $user->member->id, $user->member);

            return redirect()
                ->route('user.userDashboard')
                ->with('success', 'Permohonan pengunduran diri berhasil dikirim.');

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage() === 'storage_failed'
                ? 'Gagal menyimpan dokumen. Silakan coba lagi.'
                : 'Terjadi kesalahan saat mengajukan pengunduran diri. Silakan coba lagi.';

            return back()->withErrors([
                match($e->getMessage()) {
                    'storage_failed' => 'document',
                    default          => 'resign',
                } => $errorMsg,
            ]);
        }
    }

    public function profileShow()
    {
        $user = auth()->user();

        return Inertia::render('User/Profile/Show', [
            'user' => $this->profilPenggunaService->index($user),
        ]);
    }

    public function profileEdit()
    {
        $user = auth()->user();
        $educationOptions = array_column(EducationEnum::cases(), 'value');

        return Inertia::render('User/Profile/Edit', [
            'user' => $this->profilPenggunaService->index($user),
            'educationOptions' => $educationOptions,
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id, 'id'),
            ],
            'phone_number' => 'required|string|max:20',
            'last_education' => 'nullable|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'residential_address' => 'nullable|string|max:1000',
        ]);

        $this->profilPenggunaService->update($user, $validated);

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

        $this->profilPenggunaService->updateAvatar($user, $request->file('profile_picture'));

        return redirect()->back();
    }

    public function deleteProfilePicture()
    {
        $user = auth()->user();

        $this->profilPenggunaService->deleteAvatar($user);

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

        $this->profilPenggunaService->updatePassword($user, $validated['password']);

        return redirect()->back()->with('success', 'Password berhasil diubah');
    }
}
