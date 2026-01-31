<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function validation(string $id)
    {
        $user = User::with('userDocs', 'workUnit', 'savingAccounts', 'financings.loan')->where('status', UserStatus::RESIGNED_REQUESTED)->findOrFail($id);
        $user->userDocs()->where('name', 'Dokumen Pengunduran Diri')->first();
        $user->userDocs->first()->attachment = $user->userDocs->first()->attachment ? asset('storage/' . $user->userDocs->first()->attachment) : null;

        $totalSavings = $user->savingAccounts()->sum('balance');
        // $totalLiabilities = $user->financings()->whereHas('loan')->sum('total_price');
        return inertia('Admin/User/Resignation/Validation', [
            'data' => $user,
            'total_savings' => $totalSavings,
            // 'total_liabilities' => $totalLiabilities,
        ]);
    }

    public function validate(Request $request, string $id)
    {
        $user = User::where('status', UserStatus::RESIGNED_REQUESTED)->findOrFail($id);
        $request->status === 'reject' ? $user->status = UserStatus::RESIGNED_REJECTED : $user->status = UserStatus::INACTIVE;
        $user->save();

        return to_route('admin.resignations.index')->with('success', 'Pengunduran diri berhasil divalidasi.');
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}
