<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkUnit;

class WorkUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workUnits = [
            ['name' => 'Teknik Sipil'],
            ['name' => 'Teknik Mesin'],
            ['name' => 'Teknik Refrigerasi dan Tata Udara'],
            ['name' => 'Teknik Konversi Energi'],
            ['name' => 'Teknik Elektro'],
            ['name' => 'Teknik Kimia'],
            ['name' => 'Teknik Komputer dan Informatika'],
            ['name' => 'Akuntansi'],
            ['name' => 'Administrasi Niaga'],
            ['name' => 'Bahasa Inggris'],
        ];

        foreach ($workUnits as $workUnit) {
            WorkUnit::firstOrCreate(
                ['name' => $workUnit['name']],
                $workUnit
            );
        }
    }
}
