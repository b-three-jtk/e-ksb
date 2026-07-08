<?php

namespace Database\Factories;

use App\Models\Jaminan;
use App\Models\Pembiayaan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JaminanFactory extends Factory
{
    protected $model = Jaminan::class;

    public function definition(): array
    {
        return [
            'pembiayaan_id' => Pembiayaan::factory(),
            'jenis_jaminan' => $this->faker->randomElement(['Gold', 'Vehicle', 'Property', 'Electronics', 'Jewelry']),
            'nama_pemilik' => $this->faker->name(),
            'lokasi_kondisi_jaminan' => $this->faker->address(),
            'nilai_perkiraan_pasar' => $this->faker->numberBetween(100000, 500000000),
        ];
    }
}
