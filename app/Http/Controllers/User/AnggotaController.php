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
        $user = auth()->user()->load('anggota');

        return inertia('User/Dashboard', [
            'summary' => $this->dasborService->getSummary($user->anggota->id, $user->id),
            'tabungan'  => $this->dasborService->getTabungan($user->anggota->id),
        ]);
    }

    public function createResign()
    {
        $user = auth()->user()->load('anggota');

        $hasExistingResign = $user->anggota->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        $resignData = $this->pengunduranDiriService->getResignData($user->anggota->id);

        return inertia('User/Resign/Create', [
            'anggota'=> [
                ...$user->toArray(),
                'total_saving'     => $resignData['total_saving'],
                'total_obligation' => $resignData['total_obligation'],
            ],
            'has_existing_resign' => $hasExistingResign,
            'member_status'       => $user->anggota->status,
        ]);
    }

    public function storeResign(CreateResignRequest $request)
    {
        $user = auth()->user()->load('anggota');

        $hasExistingResign = $user->anggota->status === MemberStatusEnum::RESIGNED_REQUESTED->value;

        Log::info('User ' . $user->id . ' is trying to submit resignation with existing resign: ' . ($hasExistingResign ? 'yes' : 'no'));

        if ($hasExistingResign) {
            return back()->withErrors([
                'resign' => 'Permohonan pengunduran diri sudah pernah diajukan. Anda tidak dapat mengajukan lagi.',
            ]);
        }

        if ($user->anggota->status !== MemberStatusEnum::ACTIVE->value) {
            return back()->withErrors([
                'resign' => 'Status anggota tidak valid untuk pengajuan pengunduran diri.',
            ]);
        }

        $totalObligation = $this->pengunduranDiriService->getTotalObligation($user->anggota->id);

        if ($totalObligation > 0) {
            return back()->withErrors([
                'resign' => 'Anda masih memiliki kewajiban finansial yang belum dilunasi. Silakan selesaikan kewajiban tersebut sebelum mengajukan pengunduran diri.',
            ]);
        }

        $data = $request->validated();

        try {
            $this->pengunduranDiriService->submitResign($data['document'], $user->anggota->id, $user->anggota);

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
        $pendidikanOptions = array_column(EducationEnum::cases(), 'value');

        return Inertia::render('User/Profile/Edit', [
            'user' => $this->profilPenggunaService->index($user),
            'pendidikanOptions' => $pendidikanOptions,
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('pengguna', 'email')->ignore($user->id, 'id'),
            ],
            'no_telp' => 'required|string|max:20',
            'pendidikan_terakhir' => 'nullable|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'alamat_ktp' => 'nullable|string|max:1000',
        ]);

        $this->profilPenggunaService->update($user, $validated);

        return redirect()->route('user.profile.show');
    }

    public function updateProfilePicture(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'foto_profil' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $tmpPath = $request->file('foto_profil')->getPathname();
        if (!@getimagesize($tmpPath)) {
            return back()->withErrors(['foto_profil' => 'File tidak valid sebagai gambar.']);
        }

        $this->profilPenggunaService->updateAvatar($user, $request->file('foto_profil'));

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
