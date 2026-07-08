<?php

namespace Database\Seeders;

use App\Models\PengaturanUmum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengaturanUmumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PengaturanUmum::factory()->create([
            'key' => 'saving_pokok_amount',
            'value' => '100000',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Maximum loan amount that can be applied for by anggota.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'saving_wajib_amount',
            'value' => '100000',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Maximum loan amount that can be applied for by anggota.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'saving_point_amount',
            'value' => '100000',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Saving point conversion rate, where 1 point is equivalent to a certain amount of money.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'saving_point_reward',
            'value' => '1',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Saving point reward threshold, where anggota can redeem their points for rewards once they reach this amount.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'murabahah_margin_percentage',
            'value' => '8',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Murabahah margin percentage for loan calculations.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'murabaha_point_amount',
            'value' => '100000',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Murabaha point conversion rate, where 1 point is equivalent to a certain amount of money.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'murabaha_point_reward',
            'value' => '1',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Murabaha point reward threshold, where anggota can redeem their points for rewards once they reach this amount.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'tanggal_awal_periode',
            'value' => '2026-01-01',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Start date of the financial period.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'tanggal_akhir_periode',
            'value' => '2026-12-31',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'End date of the financial period.',
        ]);
        PengaturanUmum::factory()->create([
            'key' => 'status_tutup_buku',
            'value' => 'open',
            'tgl_diberlakukan' => now(),
            'deskripsi' => 'Status of the book closing, which can be set to open or closed.',
        ]);
    }
}
