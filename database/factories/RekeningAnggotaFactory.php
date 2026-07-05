<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\RekeningAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class RekeningAnggotaFactory extends Factory
{
    protected $model = RekeningAnggota::class;

    public function definition(): array
    {
        return [
            'no_rekening' => $this->faker->unique()->numerify('####################'),
            'nama_bank' => $this->faker->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI', 'Maybank', 'CIMB']),
            'atas_nama' => $this->faker->name(),
            'anggota_id' => Anggota::factory(),
        ];
    }
}
