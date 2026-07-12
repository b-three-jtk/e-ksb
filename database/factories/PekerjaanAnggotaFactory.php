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
            'status_pekerjaan' => fake('id_ID')->randomElement(['PNS', 'Pegawai Swasta', 'Wiraswasta', 'Mahasiswa', 'Pensiunan']),
            'jabatan_pekerjaan' => fake('id_ID')->jobTitle(),
            'nama_perusahaan' => fake('id_ID')->company(),
            'bidang_usaha' => fake('id_ID')->randomElement(['Manufaktur', 'Ritel', 'Jasa', 'Teknologi', 'Pertanian', 'Pendidikan']),
            'lama_bekerja' => fake('id_ID')->numberBetween(1, 30),
            'alamat_tempat_bekerja' => fake('id_ID')->address(),
            'no_telp_kantor' => fake('id_ID')->phoneNumber(),
        ];
    }
}
