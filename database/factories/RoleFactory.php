<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(UserRoleEnum::cases())->value,
            'guard_name' => 'web',
        ];
    }
}
