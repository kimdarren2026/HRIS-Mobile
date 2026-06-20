<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'            => fake()->randomElement(['Annual Leave', 'Sick Leave', 'Personal Leave']),
            'deducts_balance' => true,
        ];
    }
}
