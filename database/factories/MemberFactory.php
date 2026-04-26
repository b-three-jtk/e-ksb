<?php

namespace Database\Factories;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberStatusEnum;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => $this->faker->randomElement(GenderEnum::cases())->value,
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'status' => MemberStatusEnum::ACTIVE->value,
            'domicile_address' => $this->faker->address(),
            'residential_address' => $this->faker->address(),
            'marital_status' => $this->faker->randomElement(MaritalStatusEnum::cases())->value,
            'spouse_name' => $this->faker->optional()->name(),
            'last_education' => $this->faker->randomElement(EducationEnum::cases())->value,
            'dependents' => $this->faker->numberBetween(0, 5),
        ];
    }
}
