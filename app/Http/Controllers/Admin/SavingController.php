<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SavingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // dummy data
        $transaction = [
            'number' => $id,
            'status' => 'Selesai',
            'amount' => 1500000,
            'type' => 'Simpanan Sukarela',
            'category' => 'Penyetoran',
            'transaction_date' => '12 Juni 2024',
            'method' => 'Tunai',
            'description' => 'Setoran simpanan sukarela bulan Juni',
        ];

        $member = [
            'id' => 'AGT-2023001',
            'name' => 'Asep Suhendar',
            'status' => 'Aktif',
            'work_unit' => 'JTK',
        ];
        return inertia('Admin/Savings/Index', [
            'transaction' => $transaction,
            'member' => $member,
            'history' => [],
        ]);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
