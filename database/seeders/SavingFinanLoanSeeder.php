<?php

namespace Database\Seeders;

use App\Models\Financing;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SavingFinanLoanSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'anggota@example.com')->first();

        $financing = Financing::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'product_type' => 'Motor Listrik',
            'brand' => 'Ada',
            'color' => 'Putih',
            'condition' => 'Bekas',
            'description' => 'Pembiayaan motor listrik',
            'price' => 2000000,
            'qty' => 1,
            'profit' => 200000,
            'status' => 'Disetujui Dengan Catatan',
        ]);

        Loan::create([
            'financing_id' => $financing->id,
            'total_price' => 2200000,
            'tenor' => 12,
            'amount_ins' => 183333,
        ]);
    }
}
