<?php

namespace Database\Seeders;

use App\Models\JenisBarang;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JenisBarangSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisBarang = [
            ['nama_jenis_barang' => 'Kendaraan Roda Dua'],
            ['nama_jenis_barang' => 'Kendaraan Roda Empat'],
            ['nama_jenis_barang' => 'Elektronik'],
            ['nama_jenis_barang' => 'Peralatan Usaha'],
            ['nama_jenis_barang' => 'Peralatan Rumah Tangga'],
        ];

        foreach ($jenisBarang as $type) {
            JenisBarang::firstOrCreate($type);
        }
    }
}
