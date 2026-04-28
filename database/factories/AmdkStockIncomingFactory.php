<?php

namespace Database\Factories;

use App\Models\AmdkStockIncoming;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AmdkStockIncoming>
 */
class AmdkStockIncomingFactory extends Factory
{
    protected $model = AmdkStockIncoming::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => 'IN' . $this->faker->unique()->numerify('########'),
            'notes' => $this->faker->optional()->sentence(),
            'incoming_date' => $this->faker->dateTimeBetween('-30 days')->format('Y-m-d'),
            'receive_receipt' => $this->faker->optional()->word() . '_' . $this->faker->numerify('####'),
            'updated_by' => User::factory(),
        ];
    }
}
