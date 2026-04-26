<?php

namespace Database\Factories;

use App\Models\Collateral;
use App\Models\Financing;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollateralFactory extends Factory
{
    protected $model = Collateral::class;

    public function definition(): array
    {
        return [
            'financing_id' => Financing::factory(),
            'collateral_type' => $this->faker->randomElement(['Gold', 'Vehicle', 'Property', 'Electronics', 'Jewelry']),
            'collateral_proof' => $this->faker->optional()->filePath(),
            'owner_name' => $this->faker->name(),
            'collateral_location' => $this->faker->address(),
            'estimated_market_value' => $this->faker->numberBetween(100000, 500000000),
        ];
    }
}
