<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDisability>
 */
class StudentDisabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'created_by' => User::factory(),
            'cid_code' => fake()->randomElement(['F84.0', 'F90.0', 'H54.1', 'F81.0']),
            'description' => fake()->sentence(),
        ];
    }
}
