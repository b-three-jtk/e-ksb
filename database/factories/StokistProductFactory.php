<?php

namespace Database\Factories;

use App\Models\AmdkProduct;
use App\Models\StokistProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StokistProductFactory extends Factory
{
    protected $model = StokistProduct::class;

    public function definition(): array
    {
        return [
            'stokist_id' => User::factory(),
            'amdk_product_id' => AmdkProduct::factory(),
            'non_member_price' => $this->faker->numberBetween(100000, 5000000),
        ];
    }
}
