<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkUnit;
use App\Models\UserDoc;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create()
    {
        $workUnits = WorkUnit::all(['id', 'name'])->toArray();
        
        return Inertia::render('Auth/Register', [
            'workUnits' => $workUnits
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users',
            'nama_lengkap' => 'required|string',
            'nik' => 'required|digits:16|unique:users',
            'nama_lembaga' => 'required|string',
            'password' => 'required|min:8|confirmed',
            'foto_pribadi' => 'required|image|max:2048',
            'foto_ktp' => 'required|image|max:2048',
            'work_unit_id' => 'required|exists:work_units,id',
        ]);

        DB::transaction(function () use ($request, $data) {
            $profilePath = $request->file('foto_pribadi')->store('profile', 'public');
            $ktpPath = $request->file('foto_ktp')->store('ktp', 'public');

            // Get or create User role
            $userRole = Role::firstOrCreate(['name' => 'User']);

            $user = User::create([
                'id' => Str::uuid(),
                'name' => $data['nama_lengkap'],
                'email' => $data['email'],
                'nik' => $data['nik'],
                'institution' => $data['nama_lembaga'],
                'password' => Hash::make($data['password']),
                'profile_picture' => $profilePath,
                'status' => 'Dalam Peninjauan',
                'role_id' => $userRole->id,
                'work_unit_id' => $data['work_unit_id'],
            ]);

            UserDoc::create([
                'name' => 'KTP',
                'attachment' => $ktpPath,
                'user_id' => $user->id,
            ]);
        });

        return redirect()->route('register.success');
    }
}

