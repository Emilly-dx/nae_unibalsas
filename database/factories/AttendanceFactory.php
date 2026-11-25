<?php

namespace Database\Factories;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'professional_id' => User::factory(),
            'scheduled_by' => fn (array $attributes) => $attributes['professional_id'],
            'type' => fake()->randomElement(AttendanceType::cases()),
            'status' => AttendanceStatus::Scheduled,
            'modality' => AttendanceModality::Presential,
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'planned_minutes' => 50,
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (): array => [
            'status' => AttendanceStatus::Attended,
            'scheduled_at' => now()->subDay()->setTime(9, 0),
            'started_at' => now()->subDay()->setTime(9, 0),
            'ended_at' => now()->subDay()->setTime(9, 50),
            'demand' => 'Demanda de teste',
            'registered_at' => now()->subDay(),
        ]);
    }
}
