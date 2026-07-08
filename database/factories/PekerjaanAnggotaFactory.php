<?php

namespace Database\Factories;

use App\Models\Anggota;
use App\Models\PekerjaanAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

class PekerjaanAnggotaFactory extends Factory
{
    protected $model = PekerjaanAnggota::class;

    public function definition(): array
    {
        return [
            'anggota_id' => Anggota::factory(),
            'status_pekerjaan' => $this->faker->randomElement(['Employed', 'Self-Employed', 'Unemployed', 'Student', 'Retired']),
            'jabatan_pekerjaan' => $this->faker->jobTitle(),
            'nama_perusahaan' => $this->faker->company(),
            'bidang_usaha' => $this->faker->randomElement(['Manufacturing', 'Retail', 'Services', 'Technology', 'Agriculture']),
            'lama_bekerja' => $this->faker->numberBetween(1, 30),
            'alamat_tempat_bekerja' => $this->faker->address(),
            'no_telp_kantor' => $this->faker->phoneNumber(),
        ];
    }
}
