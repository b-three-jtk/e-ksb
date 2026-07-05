<?php

namespace Database\Factories;

use App\Models\KeuanganAnggota;
use App\Models\Anggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeuanganAnggotaFactory extends Factory
{
    protected $model = KeuanganAnggota::class;

    public function definition(): array
    {
        return [
            'anggota_id' => Anggota::factory(),
            'jml_gaji_pokok' => $this->faker->numberBetween(1000000, 10000000),
            'jml_penghasilan_usaha' => $this->faker->numberBetween(1000000, 10000000),
            'jml_penghasilan_pasangan' => $this->faker->numberBetween(1000000, 10000000),
            'jml_penghasilan_lainnya' => $this->faker->numberBetween(1000000, 10000000),
            'jml_biaya_hidup_keluarga' => $this->faker->numberBetween(100000, 1000000),
            'jml_biaya_pendidikan' => $this->faker->numberBetween(100000, 1000000),
            'jml_cicilan' => $this->faker->numberBetween(100000, 1000000),
            'jml_biaya_lainnya' => $this->faker->numberBetween(100000, 1000000),
        ];
    }
}
