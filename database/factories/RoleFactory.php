<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'role_name' => $this->faker->randomElement(UserRoleEnum::cases())->value,
        ];
    }
}
