<?php

namespace App\Http\Controllers\User;

use App\Enums\EducationEnum;
use App\Http\Controllers\Controller;
use App\Services\User\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
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
