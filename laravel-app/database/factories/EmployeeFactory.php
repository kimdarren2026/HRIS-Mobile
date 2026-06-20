<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $department = Department::factory()->create();

        return [
            'user_id'            => User::factory(),
            'nik'                => fake()->unique()->numerify('EMP##########'),
            'department_id'      => $department->id,
            'position_id'        => Position::factory()->create(['department_id' => $department->id])->id,
            'join_date'          => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'employment_status'  => 'active',
            'phone_number'       => fake()->numerify('08##########'),
        ];
    }
}
