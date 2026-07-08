<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\DokumenAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumenAnggotaFactory extends Factory
{
    protected $model = DokumenAnggota::class;

    public function definition(): array
    {
        return [
            'nama_dokumen' => $this->faker->randomElement(['KTP', 'Passport', 'SIM', 'Birth Certificate', 'Marriage Certificate']),
            'lampiran_dokumen' => $this->faker->filePath(),
            'anggota_id' => Anggota::factory(),
        ];
    }
}
