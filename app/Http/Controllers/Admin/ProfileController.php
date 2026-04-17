<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditProfileAdminRequest;

class ProfileController extends Controller
{
    public function show()
    {
        return inertia('Admin/Profile/Show');
    }

    public function edit()
    {
        $user = auth()->user();
        $educations = array_column(EducationEnum::cases(), 'value');

        return inertia('Admin/Profile/Edit', [
            'user' => $user,
            'educations' => $educations
        ]);
    }

    public function update(EditProfileAdminRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            if ($request->hasFile('profile_picture_file')) {
                $path = $request->file('profile_picture_file')->store('profile_pictures', 'public');
                $data['profile_picture'] = $path;
            }
            $user->update($data);

            return redirect()->route('admin.profile.show');
        } catch (\Exception $e) {
            return redirect()->back()->withInput();
        }
    }
}
