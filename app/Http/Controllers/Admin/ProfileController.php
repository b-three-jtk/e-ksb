<?php

namespace App\Http\Controllers\Admin;

use Log;
use Request;
use App\Enums\Education;
use App\Models\WorkUnit;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
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
        $workUnits = Cache::remember(
            'work_units_all',
            now()->addHours(6),
            fn() => WorkUnit::all(['id', 'name'])
        );
        $educations = array_column(Education::cases(), 'value');

        return inertia('Admin/Profile/Edit', [
            'user' => $user,
            'work_units' => $workUnits,
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
