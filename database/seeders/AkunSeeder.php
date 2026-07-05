<?php

namespace Database\Seeders;

use App\Enums\AkunCategoryEnum;
use App\Models\Akun;
use Illuminate\Database\Seeder;

class AkunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Akun::factory()->create([
            'no_ref_akun' => '101',
            'nama_akun' => 'Kas',
            'kategori_akun' => AkunCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '102',
            'nama_akun' => 'Dana Alokasi Pembiayaan Murabahah',
            'kategori_akun' => AkunCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '103',
            'nama_akun' => 'Pembiayaan Dalam Proses',
            'kategori_akun' => AkunCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '104',
            'nama_akun' => 'Piutang Murabahah',
            'kategori_akun' => AkunCategoryEnum::ASSET->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '201',
            'nama_akun' => 'Tabungan Anggota',
            'kategori_akun' => AkunCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '202',
            'nama_akun' => 'Tabungan Berjangka',
            'kategori_akun' => AkunCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '203',
            'nama_akun' => 'Tabungan Ibadah',
            'kategori_akun' => AkunCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '204',
            'nama_akun' => 'Uang Muka Murabahah',
            'kategori_akun' => AkunCategoryEnum::LIABILITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '301',
            'nama_akun' => 'Simpanan Pokok',
            'kategori_akun' => AkunCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '302',
            'nama_akun' => 'Simpanan Wajib',
            'kategori_akun' => AkunCategoryEnum::EQUITY->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        Akun::factory()->create([
            'no_ref_akun' => '401',
            'nama_akun' => 'Pendapatan Margin Murabahah',
            'kategori_akun' => AkunCategoryEnum::REVENUE->value,
            'status' => 'Aktif',
            'saldo' => 0,
        ]);

        // mengisi dana alokasi pembiayaan murabahah dengan saldo awal
        $danaAlokasiPembiayaanMurabahah = Akun::where('no_ref_akun', '102')->first();
        $danaAlokasiPembiayaanMurabahah->saldo = 200000000; // saldo awal sebesar 200 juta
        $danaAlokasiPembiayaanMurabahah->save();
    }
}
