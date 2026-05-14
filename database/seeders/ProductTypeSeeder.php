<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productTypes = [
            ['product_type_name' => 'Kendaraan Roda Dua'],
            ['product_type_name' => 'Kendaraan Roda Empat'],
            ['product_type_name' => 'Elektronik'],
            ['product_type_name' => 'Peralatan Usaha'],
            ['product_type_name' => 'Peralatan Rumah Tangga'],
        ];

        foreach ($productTypes as $type) {
            ProductType::firstOrCreate($type);
        }
    }
}
