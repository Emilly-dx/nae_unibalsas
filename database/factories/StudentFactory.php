<?php

namespace Database\Factories;

use App\Enums\Modality;
use App\Enums\Sex;
use App\Enums\Shift;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'registration' => (string) fake()->unique()->numerify('2026######'),
            'course' => fake()->randomElement([
                'Análise e Desenvolvimento de Sistemas',
                'Direito',
                'Enfermagem',
                'Agronomia',
                'Psicologia',
            ]),
            'period' => fake()->numberBetween(1, 10),
            'shift' => fake()->randomElement(Shift::cases()),
            'modality' => fake()->randomElement(Modality::cases()),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(99) 9####-####'),
            'birth_date' => fake()->dateTimeBetween('-40 years', '-17 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(Sex::cases()),
            'guardian_name' => null,
            'guardian_phone' => null,
            'guardian_relationship' => null,
            'needs_accessibility' => false,
            'accessibility_resources' => [],
            'has_external_follow_up' => false,
            'consent_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['deleted_at' => now()]);
    }
}
